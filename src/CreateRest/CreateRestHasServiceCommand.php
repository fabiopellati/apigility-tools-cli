<?php
/**
 * Created by PhpStorm.
 * User: fabio
 * Date: 15/02/18
 * Time: 14.38
 */

namespace ApigilityTools\Cli\CreateRest;

use ApigilityTools\Cli\GetInteractiveParamsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Filter\Word\CamelCaseToDash;
use Zend\Filter\Word\CamelCaseToUnderscore;

class CreateRestHasServiceCommand
    extends Command
{
    use GetInteractiveParamsTrait;
    const HELP = '';
    const HELP_ARGS = [
        'api-name'                           => 'fully qualified api namespace',
        'left-service-name'                  => 'left side service name',
        'right-service-name'                 => 'right side service name',
        'api-version'                        => 'apigility api version',
        'db-schema'                          => '',
        'db-table'                           => '',
        'db-adapter'                         => '',
        'has-table'                          => '',
        'entity-association-identifier-name' => '',
        'api-root'                           => 'root path of api module',
    ];

    protected function configure()
    {
        $this->setDescription('Create an apigility-tools standard rest service.');
        $this->setHelp(self::HELP);
        $this->addArgument('api-name', InputArgument::REQUIRED, self::HELP_ARGS['api-name']);
        $this->addArgument('left-service-name', InputArgument::REQUIRED, self::HELP_ARGS['left-service-name']);
        $this->addArgument('right-service-name', InputArgument::REQUIRED, self::HELP_ARGS['right-service-name']);
        $this->addArgument('db-schema', InputArgument::REQUIRED, self::HELP_ARGS['db-schema']);
        $this->addArgument('db-table', InputArgument::REQUIRED, self::HELP_ARGS['db-table']);
        $this->addArgument('api-root', InputArgument::OPTIONAL, self::HELP_ARGS['api-root'], '');
        $this->addOption('has-table', null, InputArgument::OPTIONAL, self::HELP_ARGS['has-table']);
        $this->addOption('db-adapter', null, InputArgument::OPTIONAL, self::HELP_ARGS['db-adapter'], 'Db\\Default');
        $this->addOption('entity-association-identifier-name', null, InputArgument::OPTIONAL,
                         self::HELP_ARGS['entity-association-identifier-name'], '');
        $this->addOption('api-version', null, InputArgument::OPTIONAL, self::HELP_ARGS['api-version'], 'v1');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $camelCaseToDash = new CamelCaseToDash();
            $camelCaseToUnderscore = new CamelCaseToUnderscore();
            $apiName = $this->getApiName($input, $output);
            $apiRoot = $this->getApiRoot($input, $output);
            $leftServiceName = $this->getOptionalArgumentValue('left-service-name', $input, $output,
                function ($answer) use ($output) {
                    if (preg_match('#[-_\.,\s]#', $answer)) {
                        throw new \RuntimeException('left-service-name must be CamelCase ');
                    }
                    return $answer;
                });
            $rightServiceName = $this->getOptionalArgumentValue('right-service-name', $input, $output,
                function ($answer) use ($output) {
                    if (preg_match('#[-_\.,\s]#', $answer)) {
                        throw new \RuntimeException('right-service-name must be CamelCase ');
                    }
                    return $answer;
                });

            $dbAdapter = $input->getOption('db-adapter');
            $dbSchema = $this->getOptionalArgumentValue('db-schema', $input, $output);
            $dbTable = $this->getOptionalArgumentValue('db-table', $input, $output);
            $version = $this->getOptionalOptionValue('api-version', $input, $output, null,1, 'v1');
            $hasTable = $this->getOptionalOptionValue('has-table',$input, $output);
            $hasTable = $hasTable ?: strtolower($camelCaseToUnderscore->filter($leftServiceName))
                . '_has_'. strtolower($camelCaseToUnderscore->filter($rightServiceName));

            $entityAssociationIdentifierName = $input->getOption('entity_association_identifier_name',$input, $output) ;
            $entityAssociationIdentifierName = $entityAssociationIdentifierName ?: strtolower($camelCaseToDash->filter($leftServiceName)) . '_id';

            $output->writeln('<comment>eseguito con i parametri:</comment>');
            $output->writeln(sprintf(
                                 '<comment> create.rest.service ' .
                                 '--api-version=%s --db-adapter=%s --entity-association-identifier-name=%s' .
                                 ' %s %s %s %s %s %s </comment>',
                                 $version, $dbAdapter, $entityAssociationIdentifierName,
                                 $apiName, $leftServiceName, $rightServiceName, $dbSchema, $dbTable, $apiRoot
                             )
            );
            $this->writeConfig($apiName, $leftServiceName, $rightServiceName, $version, $dbAdapter, $dbSchema, $dbTable,
                               $apiRoot, $hasTable, $entityAssociationIdentifierName);

            return 1;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 0;
        }
    }

    /**
     * @param $root
     *
     * @return mixed
     */
    protected function getSplitConfigPath($root)
    {

        $cwd = realpath(getcwd() . '/' . $root);
        $config = "$cwd/config";
        $autoload = "$config/autoload";
        $split = "$autoload/apigility-tools";
        if (!is_dir($config)) {
            mkdir($config);
        }
        if (!is_dir($autoload)) {
            mkdir($autoload);
        }
        if (!is_dir($split)) {
            mkdir($split);
        }

        return $split;
    }

    /**
     * @param $apiName
     * @param $leftServiceName
     * @param $rightServiceName
     * @param $dbSchema
     * @param $dbTable
     * @param $apiRoot
     */
    protected function writeConfig($apiName, $leftServiceName, $rightServiceName, $version,
                                   $dbAdapter, $dbSchema, $dbTable, $apiRoot, $hasTable,
                                   $entityAssociationIdentifierName)
    {
        $camelCaseToDash = new CamelCaseToDash();
        $template =
            file_get_contents(realpath(__DIR__ . '/../../tpl/rest-left-service-has-right-service.config.template'));
        $values = [
            strtolower($camelCaseToDash->filter($apiName)),
            $apiName,
            strtolower($camelCaseToDash->filter($leftServiceName)),
            $leftServiceName,
            strtolower($camelCaseToDash->filter($rightServiceName)),
            $rightServiceName,
            $version,
            ucfirst($version),
            $dbAdapter,
            $dbSchema,
            $dbTable,
            $hasTable,
            $entityAssociationIdentifierName,
        ];
        $keys = [
            '%api_name%',
            '%apiName%',
            '%left_service_name%',
            '%leftServiceName%',
            '%right_service_name%',
            '%rightServiceName%',
            '%version%',
            '%Version%',
            '%db_adapter%',
            '%db_schema%',
            '%db_table%',
            '%has_table%',
            '%entity_association_identifier_name%',
        ];
        $content = str_replace(
            $keys,
            $values,
            $template
        );
        $splitconfigpath = $this->getSplitConfigPath($apiRoot);
        $filename = sprintf('rest.%s.%s_has_%s.config.php', $values[6], $values[2], $values[4]);
        file_put_contents($splitconfigpath . '/' . $filename, $content);
    }
}
