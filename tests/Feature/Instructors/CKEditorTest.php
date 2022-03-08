<?php

namespace Tests\Feature\Instructors;

use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CKEditorTest extends TestCase
{
    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create(['role' => 2]);
    }

    /** @test */
    public function image_is_stored_on_s3()
    {

        $response =$this->actingAs($this->user)
            ->postJson("/api/ckeditor/upload",
                [
                    'upload' => UploadedFile::fake()->image('image.jpeg')
                ]);

        $response = strstr($response->original, "https:");
        $response = str_replace(')</script>','',$response);
        $file= pathinfo($response)['filename'] . '.jpeg';
        $this->assertTrue(Storage::disk('s3')->has("uploads/images/$file"));
    }

    /** @test */
    public function upload_must_be_an_image()
    {

        $this->actingAs($this->user)
            ->postJson("/api/ckeditor/upload",
                [
                    'upload' => UploadedFile::fake()->create('document.pdf')
                ])
            ->assertSeeText('application/pdf is not a valid mimetype.  Please verify that you are uploading an image.');
    }

    /** @test */
    public function non_instructor_cannot_upload_image()
    {
        $this->user->role = 1;
        $this->user->save();
        $this->actingAs($this->user)
            ->postJson("/api/ckeditor/upload")
            ->assertSeeText('You are not allowed to upload images.');
    }

    /** @test */
    public function upload_must_be_present()
    {
        $this->actingAs($this->user)
            ->postJson("/api/ckeditor/upload")
            ->assertSeeText('No upload present.  Please try again or contact us.');
    }


}
