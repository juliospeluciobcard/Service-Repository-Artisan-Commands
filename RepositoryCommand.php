<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {class} {--m=} {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    protected $namespace;
    protected $class;
    protected $modelClass;
    protected $file;
    protected $fileInterface;
    protected $path;
    protected $contractsPath;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->namespace = 'App\Repositories';
    }

    /**
     * Hydrate class parameters.
     *
     * @return void
     */
    private function hydrator()
    {
        $this->namespace .= "\\" . ($this->option('path') ?? 'Eloquent');
        $this->class = $this->argument('class');
        $this->modelClass = $this->option('m');
        $this->path = app_path('Repositories/' . ($this->option('path') ?? 'Eloquent'));
        $this->contractsPath = app_path('Repositories/Contracts');
        $this->file = "$this->path/$this->class.php";
        $this->fileInterface = "$this->contractsPath/$this->class" . 'Interface.php';
    }

    /**
     * Returns the contents of the file to be created.
     *
     * @return void
     */    
    private function setContents()
    {
        $template = file_get_contents(__DIR__ . './stubs/repository.stub');
        if($this->modelClass){
            $template = file_get_contents(__DIR__ . './stubs/repository.model.stub');
        }
        
        return str_replace('{{ namespace }}', $this->namespace,            
            str_replace('{{ class }}', $this->class,
            str_replace('{{ classInterface }}', $this->class . 'Interface',
            str_replace('{{ modelClass }}', $this->modelClass, $template)
        )));
    }

    /**
     * Returns the contents of the interface file to be created.
     *
     * @return void
     */    
    private function setContentsFileInterface()
    {
        $template = file_get_contents(__DIR__ . './stubs/repository-interface.stub');
        
        return str_replace('{{ namespace }}', 'App\Repositories\Contracts', str_replace('{{ class }}', $this->class, $template));
    }

    /**
     * Creates repository layer structure
     *
     * @return void
     */  
    private function scaffoldRepositories()
    {
        File::makeDirectory($this->contractsPath);
        File::put("$this->contractsPath/AbstractRepositoryInterface.php", file_get_contents(__DIR__ . './stubs/abstract-repository-interface.stub'));
        if(!File::exists(app_path('Providers/RepositoryServiceProvider.php'))){
            File::put(app_path('Providers/RepositoryServiceProvider.php'), file_get_contents(__DIR__ . './stubs/repository-service-provider.stub'));
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->hydrator();
        if(!File::exists($this->path)){
            File::makeDirectory($this->path, $mode = 0755, $recursive = true);
        }

        if(!File::exists($this->contractsPath)){
            $this->scaffoldRepositories();
        }

        if(!File::exists("$this->path/AbstractRepository.php")){
            File::put("$this->path/AbstractRepository.php", str_replace('{{ namespace }}', $this->namespace, file_get_contents(__DIR__ . './stubs/abstract-repository.stub')));
        }

        File::put($this->file, $this->setContents());
        File::put($this->fileInterface, $this->setContentsFileInterface());

        $this->info('Repository created successfully.');
    }
}