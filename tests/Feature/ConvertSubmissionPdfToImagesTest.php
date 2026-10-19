<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Jobs\ConvertSubmissionPdfToImages;
use App\SubmissionFileConversion;
use App\SubmissionFilePage;
use App\User;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * The conversion job itself. Needs pdftoppm installed (brew install poppler) — skipped otherwise.
 * Set PDFTOPPM_PATH in .env.testing if PHP can't find it on the PATH.
 */
class ConvertSubmissionPdfToImagesTest extends TestCase
{
    private $assignment;

    public function setup(): void
    {
        parent::setUp();
        $process = new Process([config('submission_pages.pdftoppm_path', 'pdftoppm'), '-v']);
        try {
            $process->run();
            $found = $process->isSuccessful();
        } catch (\Throwable $e) {
            $found = false;
        }
        if (!$found) {
            $this->markTestSkipped('pdftoppm is not installed (brew install poppler, or set PDFTOPPM_PATH).');
        }

        Storage::fake('s3');
        $instructor = factory(User::class)->create();
        $course = factory(Course::class)->create(['user_id' => $instructor->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $course->id]);
    }

    /** @test */
    public function it_renders_every_page_and_records_it()
    {
        $this->_putPdf('homework.pdf', 3);

        (new ConvertSubmissionPdfToImages($this->assignment->id, 'homework.pdf'))->handle();

        foreach ([1, 2, 3] as $page) {
            Storage::disk('s3')->assertExists("assignments/{$this->assignment->id}/pages/homework/page-$page.jpg");
        }
        $pages = SubmissionFilePage::where('assignment_id', $this->assignment->id)
            ->where('submission', 'homework.pdf')
            ->orderBy('page')
            ->get();
        $this->assertEquals([1, 2, 3], $pages->pluck('page')->all());
        foreach ($pages as $page) {
            $this->assertGreaterThan(0, $page->width);
            $this->assertGreaterThan($page->width, $page->height); // portrait letter page
        }
        $this->assertDatabaseHas('submission_file_conversions', [
            'assignment_id' => $this->assignment->id,
            'submission' => 'homework.pdf',
            'status' => SubmissionFileConversion::COMPLETED,
            'number_of_pages' => 3
        ]);
    }

    /** @test */
    public function it_handles_pdfs_with_ten_or_more_pages()
    {
        // pdftoppm zero-pads names (page-01.jpg) once there are 10+ pages
        $this->_putPdf('long.pdf', 12);

        (new ConvertSubmissionPdfToImages($this->assignment->id, 'long.pdf'))->handle();

        Storage::disk('s3')->assertExists("assignments/{$this->assignment->id}/pages/long/page-1.jpg");
        Storage::disk('s3')->assertExists("assignments/{$this->assignment->id}/pages/long/page-12.jpg");
        $this->assertEquals(12, SubmissionFilePage::where('submission', 'long.pdf')->count());
    }

    /** @test */
    public function an_uploaded_image_becomes_one_page_pointing_at_the_original()
    {
        // a 40x60 white PNG
        Storage::disk('s3')->put("assignments/{$this->assignment->id}/photo.png", base64_decode('iVBORw0KGgoAAAANSUhEUgAAACgAAAA8CAIAAACb22+3AAAAM0lEQVR4nO3NAQkAAAwDoPUvvcU4By1geiRisVgsFovFYrFYLBaLxWKxWCwWi8VisfhTPFamBYUkZyTGAAAAAElFTkSuQmCC'));

        (new ConvertSubmissionPdfToImages($this->assignment->id, 'photo.png'))->handle();

        $pages = SubmissionFilePage::where('submission', 'photo.png')->get();
        $this->assertCount(1, $pages);
        $this->assertEquals(1, $pages[0]->page);
        $this->assertEquals("assignments/{$this->assignment->id}/photo.png", $pages[0]->s3_key);
        $this->assertEquals([40, 60], [$pages[0]->width, $pages[0]->height]);
        $this->assertDatabaseHas('submission_file_conversions', [
            'submission' => 'photo.png',
            'status' => SubmissionFileConversion::COMPLETED,
            'number_of_pages' => 1
        ]);
    }

    /** @test */
    public function a_broken_pdf_is_marked_failed_and_does_not_throw()
    {
        Storage::disk('s3')->put("assignments/{$this->assignment->id}/broken.pdf", 'this is not a pdf');

        (new ConvertSubmissionPdfToImages($this->assignment->id, 'broken.pdf'))->handle();

        $conversion = SubmissionFileConversion::where('submission', 'broken.pdf')->first();
        $this->assertEquals(SubmissionFileConversion::FAILED, $conversion->status);
        $this->assertNotEmpty($conversion->error_message);
        $this->assertEquals(0, SubmissionFilePage::where('submission', 'broken.pdf')->count());
    }

    /** @test */
    public function missing_fonts_mark_the_conversion_failed_instead_of_saving_blank_pages()
    {
        // stand in for pdftoppm on a machine without fonts: it renders, exits 0, and warns on stderr
        $real_pdftoppm = config('submission_pages.pdftoppm_path', 'pdftoppm');
        $fake = tempnam(sys_get_temp_dir(), 'pdftoppm');
        file_put_contents($fake, "#!/bin/sh\n\"$real_pdftoppm\" \"\$@\"\n"
            . "echo \"Syntax Error: Couldn't find a font for 'Times-Bold'\" >&2\nexit 0\n");
        chmod($fake, 0755);
        config(['submission_pages.pdftoppm_path' => $fake]);
        $this->_putPdf('homework.pdf', 2);

        try {
            (new ConvertSubmissionPdfToImages($this->assignment->id, 'homework.pdf'))->handle();
        } finally {
            @unlink($fake);
        }

        $conversion = SubmissionFileConversion::where('submission', 'homework.pdf')->first();
        $this->assertEquals(SubmissionFileConversion::FAILED, $conversion->status);
        $this->assertStringContainsString('missing fonts (Times-Bold)', $conversion->error_message);
        $this->assertEquals(0, SubmissionFilePage::where('submission', 'homework.pdf')->count());
    }

    /** @test */
    public function a_missing_pdf_is_marked_failed()
    {
        (new ConvertSubmissionPdfToImages($this->assignment->id, 'missing.pdf'))->handle();

        $this->assertDatabaseHas('submission_file_conversions', [
            'submission' => 'missing.pdf',
            'status' => SubmissionFileConversion::FAILED
        ]);
    }

    /** @test */
    public function a_completed_conversion_is_not_redone_unless_forced()
    {
        $this->_putPdf('homework.pdf', 2);
        (new ConvertSubmissionPdfToImages($this->assignment->id, 'homework.pdf'))->handle();
        $image = "assignments/{$this->assignment->id}/pages/homework/page-1.jpg";

        Storage::disk('s3')->delete($image);
        (new ConvertSubmissionPdfToImages($this->assignment->id, 'homework.pdf'))->handle();
        Storage::disk('s3')->assertMissing($image);

        (new ConvertSubmissionPdfToImages($this->assignment->id, 'homework.pdf', true))->handle();
        Storage::disk('s3')->assertExists($image);
        $this->assertEquals(2, SubmissionFilePage::where('submission', 'homework.pdf')->count());
    }

    /** @test */
    public function the_original_pdf_is_left_untouched()
    {
        $this->_putPdf('homework.pdf', 2);
        $before = Storage::disk('s3')->get("assignments/{$this->assignment->id}/homework.pdf");

        (new ConvertSubmissionPdfToImages($this->assignment->id, 'homework.pdf'))->handle();

        $this->assertSame($before, Storage::disk('s3')->get("assignments/{$this->assignment->id}/homework.pdf"));
    }

    /**
     * @param string $filename
     * @param int $number_of_pages
     * @return void
     */
    private function _putPdf(string $filename, int $number_of_pages): void
    {
        $pdf = new Fpdi('P', 'pt', 'Letter');
        $pdf->SetFont('Helvetica', '', 24);
        for ($i = 1; $i <= $number_of_pages; $i++) {
            $pdf->AddPage();
            $pdf->Text(72, 72, "Page $i");
        }
        Storage::disk('s3')->put("assignments/{$this->assignment->id}/$filename", $pdf->Output('S'));
    }
}
