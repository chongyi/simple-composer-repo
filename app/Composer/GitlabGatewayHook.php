<?php
/**
 * GitlabGatewayHook.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/21 17:54
 */

namespace App\Composer;


use App\Exceptions\Behaviours\NoPackageEnableToUpdateException;
use App\Exceptions\Behaviours\ReadArchiveFailedException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

class GitlabGatewayHook implements GatewayHook
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Container
     */
    protected $application;

    /**
     * @var \stdClass
     */
    protected $body;

    /**
     * @var string
     */
    protected $repositoryName;

    /**
     * @var string
     */
    protected $owner;

    /**
     * @var LocalPackages
     */
    private $localPackages;

    /**
     * GitlabGatewayHook constructor.
     *
     * @param Request $request
     * @param Container $application
     */
    public function __construct(Request $request, Container $application)
    {
        $this->request = $request;
        $this->application = $application;

        $this->init();
    }

    public function run()
    {
        $event = $this->request->header('x-gitlab-event');
        $hook = $event;

        switch ($hook) {
            case 'Tag Push Hook':
                $this->tagPushHook();
                break;
            default:
        }

        return 'done';
    }

    public function tagPushHook()
    {
        $content = $this->request->getContent();
        $jsonBody = json_decode($content);

        if (!isset($jsonBody->ref)) {
            throw new \InvalidArgumentException();
        }

        if (!preg_match('#^refs/tags/(.*)#', $jsonBody->ref, $matches)) {
            throw new \InvalidArgumentException();
        }

        $tagName = $matches[1];
        if (!preg_match('#v?\d+(.\d){2}(-(a|alpha|b|beta|p|patch|dev|rc|RC)\d*)?#', $tagName)) {
            throw new \InvalidArgumentException();
        }

        $archiveUri = $jsonBody->repository->homepage . '/repository/archive.zip?ref=' . $tagName;
        $archiveHash = hash_file('md5', $archiveUri);
        $version = $tagName;

        $composerJson = $this->getComposerJsonFromArchive($archiveUri);

        if (!$this->localPackages->hasPackage($composerJson['name'])) {
            throw new NoPackageEnableToUpdateException();
        }

        $composerJson['version'] = $version;
        $composerJson['dist'] = [
            'type' => 'zip',
            'url' => $archiveUri,
            'reference' => $archiveHash,
        ];
        $composerJson['source'] = [
            'type' => 'git',
            'url' => $jsonBody->repository->git_http_url,
            'reference' => $archiveHash,
        ];

        $this->localPackages->push($composerJson['name'], $version, $composerJson);
    }

    private function init()
    {
        $this->localPackages = $this->application->make(LocalPackages::class);

        $this->body = json_decode($this->request->getContent());
        $this->repositoryName = $this->body->repository->name;

        $homepage = $this->body->repository->homepage;
        if (!preg_match(sprintf('#(\w+([-_]\w+)*)/%s$#', $this->repositoryName), $homepage, $matches)) {
            throw new \InvalidArgumentException();
        }

        $this->owner = $matches[1];
    }

    /**
     * @param string $archiveUri
     *
     * @return array
     */
    private function getComposerJsonFromArchive($archiveUri)
    {
        $zipArchive = new \ZipArchive();
        $zipArchive->open($archiveUri);

        if ($zipArchive->numFiles <= 2) {
            throw new ReadArchiveFailedException();
        }

        $firstPathName = $zipArchive->getNameIndex(0);

        if (!($stream = $zipArchive->getStream($firstPathName . 'composer.json'))) {
            throw new ReadArchiveFailedException();
        }

        $composerJsonOrigin = '';
        while (!feof($stream)) {
            $composerJsonOrigin .= fread($stream, 1024);
        }
        fclose($stream);

        return json_decode($composerJsonOrigin, true);
    }
}