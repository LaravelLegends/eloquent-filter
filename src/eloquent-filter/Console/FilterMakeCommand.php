<?php

namespace LaravelLegends\EloquentFilter\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command to make Eloquent Filters
 *
 * @author Wallace Vizerra <wallacemaxters@gmail.com>
 */
class FilterMakeCommand extends GeneratorCommand
{
    const FIELDS_BY_TYPE = [
        'datetime' => ['date_max', 'date_min', 'year_max', 'year_min', 'year_exact'],
        'string'   => ['contains', 'starts_with', 'ends_with'],
        'int'      => ['exact', 'min', 'max'],
        'key'      => ['exact', 'not_exact']

    ];  


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:filter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Model Filter';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model filter';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/filters.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Filters';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command'],
        ];
    }

    /**
     * Build classname
     *
     * @param string $name
     * @return void
     */
    protected function buildClass($name)
    {
        $template = strtr(parent::buildClass($name), ['DummyFilterables' => $this->buildFilterableArray()]);

        return $template;
    }


    public function buildFilterableArray(): string
    {

        $replacement = $this->buildRulesOfField('id', static::FIELDS_BY_TYPE['key']);

        if ($modelClass = $this->option('model')) {
            $model = new $modelClass;

            
            foreach ($model->getFillable() as $field) {

                if (in_array($field, $model->getHidden())) {
                    continue;
                }

                $type = $model->getCasts()[$field] ?? 'string';

                if ($type === 'int' || $type === 'datetime') {
                    $rules = static::FIELDS_BY_TYPE[$type];
                } else {
                    $rules = static::FIELDS_BY_TYPE['string'];
                }

                $replacement .= $this->buildRulesOfField($field, $rules);
            }

            if ($model->timestamps) {
                foreach (array_filter([$model->getCreatedAtColumn(), $model->getUpdatedAtColumn()]) as $field) {
                    $replacement .= $this->buildRulesOfField($field, static::FIELDS_BY_TYPE['datetime']);
                }
            }
        }

        return '[' . $replacement . str_repeat(' ', 8) . ']';
    }

    public function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_NONE, 'Create the filter basead on a model'],
        ];
    }

    protected function buildRulesOfField(string $field, array $rules): string
    {
        $rulesAsCode = "'" . implode("', '", $rules) . "'";
        
        $spaces = str_repeat(' ', 12);

        return "\n{$spaces}'{$field}' => [{$rulesAsCode}],\n";
    }
}
