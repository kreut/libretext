<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoveShowPointsPerQuestionToBackupTable extends Migration
{
    /**
     * source table => [backup table, id column in the backup table, short primary key name]
     * (Laravel's generated key names are longer than MySQL's 64-character limit.)
     */
    private $tables = [
        'assignments' => ['assignment_show_points_per_question_backups', 'assignment_id', 'show_points_per_question_backups_pk'],
        'assignment_templates' => ['assignment_template_show_points_per_question_backups', 'assignment_template_id', 'template_show_points_per_question_backups_pk']
    ];

    /**
     * Copy every show_points_per_question value into a backup table, then drop the column.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $source_table => [$backup_table, $id_column, $primary_key_name]) {
            if (!Schema::hasColumn($source_table, 'show_points_per_question')) {
                continue;
            }
            Schema::create($backup_table, function (Blueprint $table) use ($id_column, $primary_key_name) {
                $table->unsignedBigInteger($id_column);
                $table->unsignedTinyInteger('show_points_per_question')->nullable();
                $table->timestamps();
                $table->primary($id_column, $primary_key_name);
            });

            $now = now();
            DB::table($source_table)
                ->select('id', 'show_points_per_question')
                ->orderBy('id')
                ->chunk(1000, function ($rows) use ($backup_table, $id_column, $now) {
                    $backups = [];
                    foreach ($rows as $row) {
                        $backups[] = [
                            $id_column => $row->id,
                            'show_points_per_question' => $row->show_points_per_question,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                    DB::table($backup_table)->insert($backups);
                });

            Schema::table($source_table, function (Blueprint $table) {
                $table->dropColumn('show_points_per_question');
            });
        }
    }

    /**
     * Re-add the columns and restore the saved values. Rows created after the column
     * was dropped get the original default of 1.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $source_table => [$backup_table, $id_column]) {
            if (!Schema::hasTable($backup_table)) {
                continue;
            }
            Schema::table($source_table, function (Blueprint $table) use ($source_table) {
                $column = $table->unsignedTinyInteger('show_points_per_question')->default(1);
                if ($source_table === 'assignments') {
                    $column->after('default_points_per_question');
                }
            });

            DB::table($backup_table)
                ->orderBy($id_column)
                ->chunk(1000, function ($backups) use ($source_table, $id_column) {
                    foreach ($backups as $backup) {
                        DB::table($source_table)
                            ->where('id', $backup->{$id_column})
                            ->update(['show_points_per_question' => $backup->show_points_per_question]);
                    }
                });

            Schema::dropIfExists($backup_table);
        }
    }
}
