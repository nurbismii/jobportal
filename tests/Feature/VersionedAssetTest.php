<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionedAssetTest extends TestCase
{
    public function test_it_uses_the_public_file_timestamp_when_available()
    {
        $assetPath = 'user/css/vhire-custom.css';
        $url = versioned_asset($assetPath);

        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        $this->assertSame((string) filemtime(public_path($assetPath)), $query['v'] ?? null);
    }

    public function test_it_uses_the_configured_fallback_version_when_the_file_is_missing()
    {
        config(['app.asset_version' => 'deploy-20260417']);

        $url = versioned_asset('css/not-found.css');

        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        $this->assertSame('deploy-20260417', $query['v'] ?? null);
    }

    public function test_it_returns_the_plain_asset_url_when_no_version_is_available()
    {
        config(['app.asset_version' => null]);

        $this->assertSame(asset('css/not-found.css'), versioned_asset('css/not-found.css'));
    }
}
