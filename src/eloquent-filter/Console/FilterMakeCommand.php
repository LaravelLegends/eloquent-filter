<?php

namespace LaravelLegends\EloquentFilter\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Database\Eloquent\Model;
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
        'int'      => ['exact', 'min', 'max', 'not_equal'],
        'key'      => ['exact', 'not_equal'],
        'string'   => ['contains', 'starts_with', 'ends_with'],
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
            ['name', InputArgument::REQUIRED, 'The name of the filter'],
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
        $template = strtr(parent::buildClass($name), [
            'DummyFilterables' => $this->buildFilterableArray()
        ]);

        return $template;
    }

    protected function buildFilterableFromModel(Model $model): array
    {
        $casts   = $model->getCasts();
        $hiddens = $model->getHidden();

        $replacements = [];

        $replacements[] = $this->buildRulesOfField(
            $model->getKeyName(),
            static::FIELDS_BY_TYPE['key']
        );
        
        foreach ($model->getFillable() as $field) {
            if (in_array($field, $hiddens)) {
                continue;
            }

            $type = $casts[$field] ?? 'string';

            if (in_array($type, ['int', 'datetime'])) {
                $rules = static::FIELDS_BY_TYPE[$type];
            } else {
                $rules = static::FIELDS_BY_TYPE['string'];
            }

            $replacements[] = $this->buildRulesOfField($field, $rules);
        }

        if ($model->timestamps) {
            $timestampsFields = array_filter([
                $model->getCreatedAtColumn(),
                $model->getUpdatedAtColumn()
            ]);

            foreach ($timestampsFields as $field) {
                $replacements[] = $this->buildRulesOfField(
                    $field,
                    static::FIELDS_BY_TYPE['datetime']
                );
            }
        }

        return $replacements;
    }


    protected function buildFilterableArray(): string
    {
        if ($modelClass = $this->option('model')) {
            $replacements = $this->buildFilterableFromModel(new $modelClass);
        } else {
            $replacements = [
                $this->buildRulesOfField('id', static::FIELDS_BY_TYPE['key'])
            ];
        }

        return "[\n" . implode("\n", $replacements) . str_repeat(' ', 8) . ']';
    }

    protected function buildRulesOfField(string $field, array $rules): string
    {
        $rulesAsCode = "'" . implode("', '", $rules) . "'";
        
        $spaces = str_repeat(' ', 12);

        return "{$spaces}'{$field}' => [{$rulesAsCode}],\n";
    }

    public function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_NONE, 'Create the filter basead on a model'],
        ];
    }
}
