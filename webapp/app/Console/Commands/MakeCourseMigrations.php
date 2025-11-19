<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeCourseMigrations extends Command
{
    protected $signature = 'make:course-migrations';
    protected $description = 'Create migrations for ri_course_ancestor_stats and ri_course_inbreed_stats';

    public function handle()
    {
        $timestamp = date('Y_m_d_His');

        // パス
        $path1 = database_path("migrations/{$timestamp}_create_ri_course_ancestor_stats_table.php");
        usleep(500000); // タイムスタンプ重複防止
        $timestamp2 = date('Y_m_d_His');
        $path2 = database_path("migrations/{$timestamp2}_create_ri_course_inbreed_stats_table.php");

        // ▼ マイグレーション 1：ri_course_ancestor_stats
        $ancestorStub = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ri_course_ancestor_stats', function (Blueprint \$table) {
            \$table->id();

            \$table->string('course_key', 20);
            \$table->enum('grade_group', ['G1','G2','G3','JYUSHO','OP','ALL']);
            \$table->integer('distance');
            \$table->enum('track_type', ['TURF','DIRT','STEEP','UNKNOWN']);
            \$table->string('years_range', 20);

            \$table->string('ancestor_uid', 20);
            \$table->string('ancestor_name', 100)->nullable();

            \$table->integer('start_count');
            \$table->integer('win_count');
            \$table->integer('place_count');
            \$table->integer('show_count');
            \$table->integer('board_count');
            \$table->integer('out_of_board_count');

            \$table->decimal('avg_blood_share', 6, 5)->nullable();

            \$table->timestamp('created_at')->useCurrent();
            \$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            \$table->unique(['course_key','grade_group','years_range','ancestor_uid'], 'uniq_course_ancestor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_course_ancestor_stats');
    }
};
PHP;

        // ▼ マイグレーション 2：ri_course_inbreed_stats
        $inbreedStub = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ri_course_inbreed_stats', function (Blueprint \$table) {
            \$table->id();

            \$table->string('course_key', 20);
            \$table->enum('grade_group', ['G1','G2','G3','JYUSHO','OP','ALL']);
            \$table->integer('distance');
            \$table->enum('track_type', ['TURF','DIRT','STEEP','UNKNOWN']);
            \$table->string('years_range', 20);

            \$table->string('ancestor_uid', 20);
            \$table->string('ancestor_name', 100)->nullable();
            \$table->enum('inbreed_degree', ['弱','中','強','未知'])->nullable();

            \$table->integer('start_count');
            \$table->integer('win_count');
            \$table->integer('place_count');
            \$table->integer('show_count');
            \$table->integer('board_count');
            \$table->integer('out_of_board_count');

            \$table->decimal('avg_cross_percent', 5, 2)->nullable();

            \$table->timestamp('created_at')->useCurrent();
            \$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            \$table->unique(['course_key','grade_group','years_range','ancestor_uid'], 'uniq_course_inbreed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_course_inbreed_stats');
    }
};
PHP;

        // ファイル作成
        File::put($path1, $ancestorStub);
        File::put($path2, $inbreedStub);

        $this->info("Created:");
        $this->info("  - " . basename($path1));
        $this->info("  - " . basename($path2));

        return Command::SUCCESS;
    }
}
