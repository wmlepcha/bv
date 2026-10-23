<?php

namespace Tests\Feature\TrainingManager;

use App\Models\MaterialFile;
use App\Models\User;
use App\Services\MaterialLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YoutubePlaylistTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_add_youtube_playlist_from_playlist_url(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->post(route('training-manager.library.youtube-playlist.store', $folder), [
                'url' => 'https://www.youtube.com/playlist?list=PLrAXtmRdnEQy6nuLMOV8u4ExDWAYO-c1q',
                'title' => 'A1 Grammar series',
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $folder->id]))
            ->assertSessionHas('success', 'YouTube playlist added.');

        $playlist = MaterialFile::query()
            ->where('folder_id', $folder->id)
            ->where('kind', MaterialFile::KIND_YOUTUBE_PLAYLIST)
            ->first();

        $this->assertNotNull($playlist);
        $this->assertSame('A1 Grammar series', $playlist->display_name);
        $this->assertSame(
            'https://www.youtube.com/playlist?list=PLrAXtmRdnEQy6nuLMOV8u4ExDWAYO-c1q',
            $playlist->url,
        );
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/videoseries?list=PLrAXtmRdnEQy6nuLMOV8u4ExDWAYO-c1q',
            $playlist->embedUrl(),
        );
    }

    public function test_training_manager_can_add_youtube_playlist_from_watch_url_with_list(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->post(route('training-manager.library.youtube-playlist.store', $folder), [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLrAXtmRdnEQy6nuLMOV8u4ExDWAYO-c1q',
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $folder->id]));

        $playlist = MaterialFile::query()
            ->where('folder_id', $folder->id)
            ->where('kind', MaterialFile::KIND_YOUTUBE_PLAYLIST)
            ->first();

        $this->assertNotNull($playlist);
        $this->assertSame('YouTube playlist', $playlist->display_name);
        $this->assertSame(
            'https://www.youtube.com/playlist?list=PLrAXtmRdnEQy6nuLMOV8u4ExDWAYO-c1q',
            $playlist->url,
        );
    }

    public function test_adding_playlist_with_api_key_stores_playlist_items(): void
    {
        config(['services.youtube.api_key' => 'test-youtube-key']);

        Http::fake([
            'googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Tutorial #1',
                            'resourceId' => ['videoId' => 'abc123xyz01'],
                        ],
                    ],
                    [
                        'snippet' => [
                            'title' => 'Tutorial #2',
                            'resourceId' => ['videoId' => 'def456uvw02'],
                        ],
                    ],
                ],
            ]),
        ]);

        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->post(route('training-manager.library.youtube-playlist.store', $folder), [
                'url' => 'https://www.youtube.com/playlist?list=PL4cUxeGkcC9g4YJeBqChhFJwKQ9TRiivY',
                'title' => 'Net Ninja',
            ])
            ->assertRedirect();

        $playlist = MaterialFile::query()
            ->where('folder_id', $folder->id)
            ->where('kind', MaterialFile::KIND_YOUTUBE_PLAYLIST)
            ->first();

        $this->assertNotNull($playlist);
        $this->assertSame([
            ['video_id' => 'abc123xyz01', 'title' => 'Tutorial #1'],
            ['video_id' => 'def456uvw02', 'title' => 'Tutorial #2'],
        ], $playlist->playlist_items);
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/abc123xyz01?list=PL4cUxeGkcC9g4YJeBqChhFJwKQ9TRiivY',
            $playlist->embedUrl(),
        );
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/def456uvw02?list=PL4cUxeGkcC9g4YJeBqChhFJwKQ9TRiivY',
            $playlist->embedUrlForVideo('def456uvw02'),
        );
    }

    public function test_adding_playlist_without_api_key_leaves_items_empty(): void
    {
        config(['services.youtube.api_key' => null]);

        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $playlist = $library->addYoutubePlaylist(
            $manager,
            $folder,
            'https://www.youtube.com/playlist?list=PL4cUxeGkcC9g4YJeBqChhFJwKQ9TRiivY',
            'Series',
        );

        $this->assertNull($playlist->playlist_items);
    }

    public function test_youtube_playlist_endpoint_rejects_watch_later(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.youtube-playlist.store', $folder), [
                'url' => 'https://www.youtube.com/playlist?list=WL',
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_youtube_playlist_endpoint_rejects_video_only_urls(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.youtube-playlist.store', $folder), [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseMissing('material_files', [
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_YOUTUBE_PLAYLIST,
        ]);
    }

    public function test_youtube_playlist_endpoint_rejects_invalid_urls(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.youtube-playlist.store', $folder), [
                'url' => 'https://example.com/not-youtube',
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_link_endpoint_rejects_youtube_playlist_urls(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.links.store', $folder), [
                'url' => 'https://www.youtube.com/playlist?list=PLrAXtmRdnEQy6nuLMOV8u4ExDWAYO-c1q',
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_download_returns_not_found_for_youtube_playlist_materials(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $playlist = $library->addYoutubePlaylist(
            $manager,
            $folder,
            'https://www.youtube.com/playlist?list=PLrAXtmRdnEQy6nuLMOV8u4ExDWAYO-c1q',
            'Series',
        );

        $this->actingAs($manager)
            ->get(route('training-manager.library.files.download', $playlist))
            ->assertNotFound();
    }

    public function test_playlist_items_endpoint_returns_cached_items(): void
    {
        config(['services.youtube.api_key' => null]);

        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $playlist = $library->addYoutubePlaylist(
            $manager,
            $folder,
            'https://www.youtube.com/playlist?list=PL4cUxeGkcC9g4YJeBqChhFJwKQ9TRiivY',
            'Cached',
        );
        $playlist->forceFill([
            'playlist_items' => [
                ['video_id' => 'abc123xyz01', 'title' => 'Intro'],
            ],
        ])->save();

        $this->actingAs($manager)
            ->getJson(route('training-manager.library.files.playlist-items', $playlist))
            ->assertOk()
            ->assertJson([
                'items' => [
                    ['video_id' => 'abc123xyz01', 'title' => 'Intro'],
                ],
                'api_configured' => false,
            ]);
    }

    public function test_playlist_items_endpoint_fetches_when_cache_empty(): void
    {
        config(['services.youtube.api_key' => null]);

        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $playlist = $library->addYoutubePlaylist(
            $manager,
            $folder,
            'https://www.youtube.com/playlist?list=PL4cUxeGkcC9g4YJeBqChhFJwKQ9TRiivY',
            'Backfill',
        );

        $this->assertNull($playlist->fresh()->playlist_items);

        config(['services.youtube.api_key' => 'test-youtube-key']);

        Http::fake([
            'googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Fetched video',
                            'resourceId' => ['videoId' => 'zzz999yyy88'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($manager)
            ->getJson(route('training-manager.library.files.playlist-items', $playlist))
            ->assertOk()
            ->assertJson([
                'items' => [
                    ['video_id' => 'zzz999yyy88', 'title' => 'Fetched video'],
                ],
                'api_configured' => true,
            ]);

        $this->assertSame(
            [['video_id' => 'zzz999yyy88', 'title' => 'Fetched video']],
            $playlist->fresh()->playlist_items,
        );
    }
}
