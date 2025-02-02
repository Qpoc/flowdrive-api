<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-feature {version} {feature_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new feature.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lowerCaseVersion = strtolower($this->argument('version'));
        $directories = [
            "{$this->argument('version')}/Models",
            "{$this->argument('version')}/Services",
            "{$this->argument('version')}/Request",
            "{$this->argument('version')}/Routes",
            "{$this->argument('version')}/Controllers"
        ];

        foreach ($directories as $directory) {
            $this->info("Creating {$directory} directory...");
            File::makeDirectory(base_path("src/Features/{$this->argument('feature_name')}/{$directory}"), 0755, true);
        }

        File::put(base_path("src/Features/{$this->argument('feature_name')}/{$this->argument('version')}/Routes/api.php"), "
<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('{$lowerCaseVersion}')->group(function () {

});
        ");
    }
}
