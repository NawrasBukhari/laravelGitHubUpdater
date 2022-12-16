<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\SourceRepositoryTypes\GithubRepositoryTypes;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NawrasBukhari\Updater\Contracts\SourceRepositoryTypeContract;
use NawrasBukhari\Updater\Exceptions\ReleaseException;
use NawrasBukhari\Updater\Models\Release;
use NawrasBukhari\Updater\Models\UpdateExecutor;
use NawrasBukhari\Updater\SourceRepositoryTypes\GithubRepositoryType;

final class GithubBranchType extends GithubRepositoryType implements SourceRepositoryTypeContract
{
    protected ClientInterface $client;

    protected Release $release;

    public function __construct(UpdateExecutor $updateExecutor)
    {
        parent::__construct(config('self-update.repository_types.github'), $updateExecutor);
        $this->release = resolve(Release::class);
        $this->release->setStoragePath(Str::finish($this->config['download_path'], urlSeparator()))
                      ->setUpdatePath(base_path(), config('self-update.exclude_folders'))
                      ->setAccessToken($this->config['private_access_token']);
    }

    public function fetch(string $version = ''): Release
    {
        $response = $this->getReleases();

        try {
            $releases = collect(Utils::jsonDecode($response->body()));
        } catch (InvalidArgumentException $e) {
            throw ReleaseException::noReleaseFound($version);
        }

        if ($releases->isEmpty()) {
            throw ReleaseException::noReleaseFound($version);
        }

        $release = $this->selectRelease($releases, $version);

        $this->release->setVersion($release->commit->author->date)
                      ->setRelease($release->sha.'.zip')
                      ->updateStoragePath()
                      ->setDownloadUrl($this->generateArchiveUrl($release->sha));

        if (! $this->release->isSourceAlreadyFetched()) {
            $this->release->download();
            $this->release->extract();
        }

        return $this->release;
    }

    public function selectRelease(Collection $collection, string $version)
    {
        $release = $collection->first();

        if (! empty($version)) {
            if ($collection->contains('commit.author.date', $version)) {
                $release = $collection->where('commit.author.date', $version)->first();
            } else {
                Log::info('No commit for date "'.$version.'" found. Selecting latest.');
            }
        }

        return $release;
    }

    public function getVersionAvailable(string $prepend = '', string $append = ''): string
    {
        if ($this->versionFileExists()) {
            $version = $prepend.$this->getVersionFile().$append;
        } else {
            $response = $this->getReleases();
            $releaseCollection = collect(Utils::jsonDecode($response->body()));
            $version = $prepend.$releaseCollection->first()->commit->author->date.$append;
        }

        return $version;
    }

    final public function getReleases(): Response
    {
        $url = urlSeparator().'repos'
               .urlSeparator().$this->config['repository_vendor']
               .urlSeparator().$this->config['repository_name']
               .urlSeparator().'commits'
               .'?sha='.$this->config['use_branch'];

        $headers = [];

        if ($this->release->hasAccessToken()) {
            $headers = [
                'Authorization' => $this->release->getAccessToken(),
            ];
        }

        return Http::withHeaders($headers)->baseUrl(self::BASE_URL)->get($url);
    }

    private function generateArchiveUrl(string $name): string
    {
        return  urlSeparator().'repos'
               .urlSeparator().$this->config['repository_vendor']
               .urlSeparator().$this->config['repository_name']
               .urlSeparator().'zipball'
               .urlSeparator().$name;
    }
}
