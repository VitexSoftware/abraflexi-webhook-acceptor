<?php

declare(strict_types=1);

/**
 * This file is part of the MultiFlexi package
 *
 * https://github.com/VitexSoftware/abraflexi-webhook-acceptor
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AbraFlexi\Acceptor\Installer;

/**
 * Installer wizard logic: verification of every step against AbraFlexi,
 * session state handling and configuration persistence.
 *
 * @author Vítězslav Dvořák <vitex@vitexsoftware.com>
 */
class Wizard
{
    public const STEP_ENDPOINT = 1;
    public const STEP_CREDENTIALS = 2;
    public const STEP_COMPANY = 3;

    private const SESSION_KEY = 'wha_wizard';

    /**
     * Keys stored to .env after successful installation.
     *
     * @var array<string, string>
     */
    private const ENV_MAP = [
        'url' => 'ABRAFLEXI_URL',
        'user' => 'ABRAFLEXI_LOGIN',
        'password' => 'ABRAFLEXI_PASSWORD',
        'authSessionId' => 'ABRAFLEXI_AUTHSESSID',
        'company' => 'ABRAFLEXI_COMPANY',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $state;

    public function __construct()
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY]) || !\is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = ['verified' => 0];
        }

        $this->state = &$_SESSION[self::SESSION_KEY];

        if (empty($_SESSION['wha_csrf'])) {
            $_SESSION['wha_csrf'] = bin2hex(random_bytes(16));
        }
    }

    public function getCsrfToken(): string
    {
        return (string) $_SESSION['wha_csrf'];
    }

    public function checkCsrfToken(?string $token): bool
    {
        return \is_string($token) && hash_equals($this->getCsrfToken(), $token);
    }

    /**
     * Highest step the user is allowed to see.
     */
    public function maxAllowedStep(): int
    {
        return min(self::STEP_COMPANY, (int) $this->state['verified'] + 1);
    }

    public function clampStep(int $requested): int
    {
        return max(self::STEP_ENDPOINT, min($requested, $this->maxAllowedStep()));
    }

    /**
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->state[$key] ?? $default;
    }

    public function reset(): void
    {
        $this->state = ['verified' => 0];
    }

    /**
     * Step 1: is the URL an AbraFlexi REST API endpoint?
     *
     * @throws \InvalidArgumentException with user readable message
     */
    public function verifyEndpoint(string $url): string
    {
        $url = rtrim(trim($url), '/');
        $parts = parse_url($url);

        if ($url === '' || filter_var($url, \FILTER_VALIDATE_URL) === false || !isset($parts['scheme'], $parts['host']) || !\in_array($parts['scheme'], ['http', 'https'], true)) {
            throw new \InvalidArgumentException(_('Invalid URL. Use format like https://abraflexi.example.com:5434'));
        }

        $company = $this->companyClient(['url' => $url]);
        $company->getFlexiData();
        $code = (int) $company->lastResponseCode;

        // 200 = anonymous access allowed, 401 = AbraFlexi asking for credentials
        if ($code !== 200 && $code !== 401) {
            throw new \InvalidArgumentException($code === 0 ? sprintf(_('Server %s is not reachable'), $url) : sprintf(_('%s does not look like an AbraFlexi REST API (HTTP %d)'), $url, $code));
        }

        $this->state = ['verified' => self::STEP_ENDPOINT, 'url' => $url];

        return $url;
    }

    /**
     * Step 2: do the credentials work?
     *
     * @param array<string, string> $input user, password and/or authSessionId
     *
     * @throws \InvalidArgumentException with user readable message
     */
    public function verifyCredentials(array $input, bool $useApiKey): void
    {
        $opts = ['url' => $this->state['url'] ?? ''];

        if ($useApiKey) {
            $key = trim($input['authSessionId'] ?? '');

            if ($key === '') {
                throw new \InvalidArgumentException(_('Enter the API key'));
            }

            $opts['authSessionId'] = $key;
        } else {
            $user = trim($input['user'] ?? '');

            if ($user === '' || ($input['password'] ?? '') === '') {
                throw new \InvalidArgumentException(_('Enter login and password'));
            }

            $opts['user'] = $user;
            $opts['password'] = $input['password'];
        }

        $company = $this->companyClient($opts);
        $company->getFlexiData();

        if ((int) $company->lastResponseCode !== 200) {
            throw new \InvalidArgumentException(\in_array((int) $company->lastResponseCode, [401, 403], true) ? _('Login failed: wrong credentials') : sprintf(_('Login failed (HTTP %d)'), (int) $company->lastResponseCode));
        }

        foreach (['user', 'password', 'authSessionId'] as $key) {
            unset($this->state[$key]);
        }

        $this->state = array_merge($this->state, array_diff_key($opts, ['url' => 1]), ['verified' => self::STEP_CREDENTIALS]);
    }

    /**
     * Companies available with verified credentials.
     *
     * @return array<string, string> dbNazev => nazev
     */
    public function listCompanies(): array
    {
        $company = $this->companyClient($this->connectionOptions());
        $rows = $company->getFlexiData();
        $companies = [];

        if (\is_array($rows)) {
            foreach ($rows as $row) {
                if (\is_array($row) && !empty($row['dbNazev'])) {
                    $companies[$row['dbNazev']] = (string) ($row['nazev'] ?? $row['dbNazev']);
                }
            }
        }

        return $companies;
    }

    /**
     * Step 3: register hook and save configuration.
     *
     * @return string registered hook URL
     *
     * @throws \InvalidArgumentException with user readable message
     */
    public function install(string $companyCode, string $hookUrl, string $envFile): string
    {
        if (!\array_key_exists($companyCode, $this->listCompanies())) {
            throw new \InvalidArgumentException(_('Selected company is not available'));
        }

        $opts = $this->connectionOptions() + ['company' => $companyCode];
        $hooker = new \AbraFlexi\Hooks(null, $this->explicitOptions($opts));
        $url = \Ease\Functions::addUrlParams($hookUrl, ['company' => $companyCode]);

        if (!$hooker->register($url)) {
            throw new \InvalidArgumentException(sprintf(_('Hook %s was not registered'), $url));
        }

        $this->state['company'] = $companyCode;
        $this->saveEnv($envFile, $opts);

        return $url;
    }

    /**
     * Update only the ABRAFLEXI_* keys in the .env file.
     *
     * @param array<string, string> $opts
     *
     * @throws \InvalidArgumentException when the file is not writable
     */
    public function saveEnv(string $envFile, array $opts): void
    {
        $lines = file_exists($envFile) ? file($envFile, \FILE_IGNORE_NEW_LINES) : [];
        $values = [];

        foreach (self::ENV_MAP as $key => $envKey) {
            $values[$envKey] = $opts[$key] ?? '';
        }

        $written = [];

        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*([A-Z_]+)\s*=/', $line, $m) && \array_key_exists($m[1], $values)) {
                $lines[$i] = $m[1].'='.$this->quote($values[$m[1]]);
                $written[$m[1]] = true;
            }
        }

        foreach ($values as $envKey => $value) {
            if (!isset($written[$envKey])) {
                $lines[] = $envKey.'='.$this->quote($value);
            }
        }

        if (@file_put_contents($envFile, implode("\n", $lines)."\n") === false) {
            throw new \InvalidArgumentException(sprintf(_('Cannot write %s. Add manually: %s'), $envFile, implode(' ', array_map(static fn ($k, $v) => $k.'='.($k === 'ABRAFLEXI_PASSWORD' ? '***' : $v), array_keys($values), $values))));
        }
    }

    /**
     * @return array<string, string>
     */
    private function connectionOptions(): array
    {
        return array_intersect_key($this->state, array_flip(['url', 'user', 'password', 'authSessionId']));
    }

    /**
     * Fill unused connection options with empty values.
     *
     * AbraFlexi client falls back to ABRAFLEXI_* environment (the current .env)
     * for every option that is missing. Without this a configured ABRAFLEXI_COMPANY
     * turns the /c.json company list into a single company and ABRAFLEXI_LOGIN/PASSWORD
     * would silently authenticate an API key check.
     *
     * @param array<string, string> $opts
     *
     * @return array<string, string>
     */
    private function explicitOptions(array $opts): array
    {
        return $opts + ['company' => '', 'user' => '', 'password' => '', 'authSessionId' => ''];
    }

    /**
     * @param array<string, string> $opts
     */
    private function companyClient(array $opts): \AbraFlexi\Company
    {
        return new \AbraFlexi\Company(null, $this->explicitOptions($opts) + ['throwException' => false, 'autoload' => false, 'timeout' => 10]);
    }

    private function quote(string $value): string
    {
        return $value === '' || preg_match('/^[A-Za-z0-9_.:\/@-]+$/', $value) ? $value : '"'.addcslashes($value, '"\\').'"';
    }
}
