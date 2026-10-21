<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\Attributes\Test;
use App\User;
use Tests\TestCase;

class MetricsTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create(['id' => 99]);//not admin
    }

    #[Test]
    public function cannot_get_cell_data_if_not_admin()
    {
        $this->actingAs($this->user)->getJson("/api/metrics/0")
            ->assertJson(['message' => 'You are not allowed to get the metrics.']);
    }

    #[Test]
    public function cannot_get_metrics_if_not_admin()
    {
        $this->actingAs($this->user)->getJson("/api/metrics/cell-data/0")
            ->assertJson(['message' => 'You are not allowed to get the cell data.']);

    }
}
