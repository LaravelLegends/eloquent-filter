<?php

use Illuminate\Console\Command;
use LaravelLegends\EloquentFilter\Console\FilterMakeCommand;
use LaravelLegends\EloquentFilter\Providers\FilterServiceProvider;

class FilterMakeCommandTest extends Orchestra\Testbench\TestCase
{
    
    public function setUp(): void
    {
        parent::setUp();

        $this->app['path'] = sys_get_temp_dir() . '/laravel';
    }

    public function testInstance()
    {
        $cmd = $this->app[FilterMakeCommand::class];

        $this->assertInstanceOf(Command::class, $cmd);
    }

    public function testConsoleCommand()
    {
        $command = $this->artisan(
            'make:filter',
            ['name' => 'UserFilter', '--force']
        );

        $command->assertExitCode(0);
        $command->run();

        
    }

    protected function getPackageProviders($app)
    {
        return [FilterServiceProvider::class];
    }
}
