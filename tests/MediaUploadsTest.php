<?php

namespace BWibrew\SiteSettings\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use BWibrew\SiteSettings\Tests\Models\User;
use Spatie\MediaLibrary\FileAdder\FileAdder;
use BWibrew\SiteSettings\Tests\Models\Setting;

class MediaUploadsTest extends TestCase
{
    protected $setting;
    protected $file;

    public function setUp()
    {
        parent::setUp();

        $this->setting = factory(Setting::class)->create();
        $this->file = UploadedFile::fake()->image('logo.png')->size(100);
    }

    /** @test */
    public function it_adds_media()
    {
        $this->addFileToMediaCollection($this->setting->addMedia($this->file));

        $this->assertCount(1, $this->setting->getMedia());

        Storage::disk('public')->assertExists($this->getStoragePath($this->setting->id));
    }

    /** @test */
    public function it_deletes_media()
    {
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());
        $this->addFileToMediaCollection($this->setting->addMedia($this->file));
        $path = $this->getStoragePath($this->setting->id);

        $this->setting->updateValue('foobar', true);

        $this->setting = Setting::find($this->setting->id);

        $this->assertEquals(null, $this->setting->value);
        $this->assertCount(0, $this->setting->getMedia());

        Storage::disk('public')->assertMissing($path);
    }

    /** @test */
    public function it_registers_a_file_upload()
    {
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $setting = Setting::register('upload', $this->file);

        $this->assertEquals('logo.png', $setting->value);
        $this->assertCount(1, $setting->getMedia());

        Storage::disk('public')->assertExists($this->getStoragePath($setting->id));
    }

    /** @test */
    public function it_registers_a_file_upload_with_a_scope()
    {
        $this->app['config']->set('sitesettings.use_scopes', true);
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $setting = Setting::register('scope.name', $this->file);

        $this->assertEquals('name', $setting->name);
        $this->assertEquals('scope', $setting->scope);
        $this->assertEquals('logo.png', $setting->value);
        $this->assertCount(1, $setting->getMedia());

        Storage::disk('public')->assertExists($this->getStoragePath($setting->id));
    }

    /** @test */
    public function it_updates_with_a_file_upload()
    {
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $this->setting->updateValue($this->file);

        $this->assertEquals('logo.png', $this->setting->value);
        $this->assertCount(1, $this->setting->getMedia());

        Storage::disk('public')->assertExists($this->getStoragePath($this->setting->id));
    }

    /** @test */
    public function it_updates_with_a_scope_with_a_file_upload()
    {
        $this->app['config']->set('sitesettings.use_scopes', true);
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $this->setting->updateValue($this->file);

        $this->assertEquals('logo.png', $this->setting->value);
        $this->assertEquals($this->setting->scope, $this->setting->scope);

        Storage::disk('public')->assertExists($this->getStoragePath($this->setting->id));
    }

    /** @test */
    public function it_updates_updated_by_with_a_file_upload()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user);
        $this->setting->updateValue($this->file);

        $this->assertInternalType('int', $this->setting->updated_by);
        $this->assertEquals($user->id, $this->setting->updated_by);
    }

    /** @test */
    public function it_sets_updated_by_when_registering()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $setting = Setting::register('name', $this->file);

        $this->assertInternalType('int', $setting->updated_by);
        $this->assertEquals($user->id, $setting->updated_by);
    }

    /** @test */
    public function it_gets_updated_by()
    {
        $user = factory(User::class)->create();
        Auth::shouldReceive('user')->andReturn($user);

        $this->setting->updateValue($this->file);

        $user_id = Setting::getUpdatedBy($this->setting->name);
        $this->assertInternalType('int', $user_id);
        $this->assertEquals($user->id, $user_id);
    }

    /** @test */
    public function it_gets_updated_at()
    {
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $this->setting->updateValue($this->file);

        $timestamp = Setting::getUpdatedAt($this->setting->name);
        $this->assertEquals($this->setting->updated_at, $timestamp);
    }

    /** @test */
    public function it_stores_single_file_per_setting()
    {
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());
        $updated = UploadedFile::fake()->image('logo2.png')->size(100);

        $this->setting->updateValue($this->file);
        $this->assertCount(1, Setting::find($this->setting->id)->getMedia());
        $this->assertEquals('logo.png', Setting::find($this->setting->id)->getMedia()->first()->file_name);

        Storage::disk('public')->assertExists($this->getStoragePath($this->setting->id));

        $this->setting->updateValue($updated);
        $this->assertCount(1, Setting::find($this->setting->id)->getMedia());
        $this->assertEquals('logo2.png', Setting::find($this->setting->id)->getMedia()->first()->file_name);

        Storage::disk('public')->assertExists($this->getStoragePath($this->setting->id));
    }

    /** @test */
    public function it_gets_filename_when_set_in_config()
    {
        $this->app['config']->set('sitesettings.media_value_type', 'file_name');
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $setting = Setting::register('upload', $this->file);

        $this->assertEquals('logo.png', $setting->value);
        $this->assertCount(1, $setting->getMedia());
    }

    /** @test */
    public function it_gets_url_when_set_in_config()
    {
        $this->app['config']->set('sitesettings.media_value_type', 'url');
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $setting = Setting::register('upload', $this->file);

        $this->assertEquals($setting->getMedia()->first()->getUrl(), $setting->value);
        $this->assertCount(1, $setting->getMedia());
    }

    /** @test */
    public function it_gets_file_path_when_set_in_config()
    {
        $this->app['config']->set('sitesettings.media_value_type', 'path');
        Auth::shouldReceive('user')->andReturn(factory(User::class)->create());

        $setting = Setting::register('upload', $this->file);

        $this->assertEquals($setting->getMedia()->first()->getPath(), $setting->value);
        $this->assertCount(1, $setting->getMedia());
    }

    /**
     * Ensure compatibility with multiple versions of Spatie Media Library.
     *
     * @param FileAdder $fileAdder
     *
     * @throws \Spatie\MediaLibrary\Exceptions\FileCannotBeAdded
     */
    protected function addFileToMediaCollection(FileAdder $fileAdder)
    {
        if (method_exists($fileAdder, 'toMediaCollection')) {
            // spatie/laravel-medialibrary v6
            $fileAdder->toMediaCollection();
        } elseif (method_exists($fileAdder, 'toMediaLibrary')) {
            // spatie/laravel-medialibrary v5
            $fileAdder->toMediaLibrary();
        }
    }
}
