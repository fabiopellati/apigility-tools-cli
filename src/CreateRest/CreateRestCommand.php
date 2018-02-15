<?php
/**
 * Created by PhpStorm.
 * User: fabio
 * Date: 15/02/18
 * Time: 14.38
 */

namespace ApigilityTools\Cli\CreateRest;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Filter\Word\CamelCaseToDash;

class CreateRestCommand
    extends Command
{
    const HELP = '';
    const HELP_ARGS = [
        'api-name'               => 'fully qualified api namespace',
        'service-name'           => '',
        'version'                => '',
        'route-basepath'         => '',
        'route-identifier-name'  => '',
        'collection-name'        => '',
        'entity-identifier-name' => '',
    ];

    protected function configure()
    {
        $this->setDescription('Create an apigility-tools standard rest service.');
        $this->setHelp(self::HELP);
        $this->addArgument('api-name', InputArgument::REQUIRED, self::HELP_ARGS['api-name']);
        $this->addArgument('service-name', InputArgument::REQUIRED, self::HELP_ARGS['service-name']);
        $this->addOption('version', null,InputArgument::OPTIONAL, self::HELP_ARGS['version'], 'v1');
        $this->addOption('route-basepath', null,InputArgument::OPTIONAL, self::HELP_ARGS['route-basepath'],'');
        $this->addOption('route-identifier-name', null,InputArgument::OPTIONAL, self::HELP_ARGS['route-identifier-name'],'');
        $this->addOption('collection-name',null, InputArgument::OPTIONAL, self::HELP_ARGS['collection-name'],'');
        $this->addOption('entity-identifier-name', null,InputArgument::OPTIONAL,
                           self::HELP_ARGS['entity-identifier-name'],'');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        print_r([__METHOD__=>__LINE__,$input->getArguments(), $input->getOptions()]);exit;


        $template = $this->getTemplate();
        $api_name = $input->getArgument('api-name');
        $serviceName = $input->getArgument('service-name');
        $version = $input->getArgument('version');
        $route_basepath = $input->getArgument('api-name');
        $route_identifier_name = $input->getArgument('route-identifier-name');
        $collection_name = $input->getArgument('collection-name');
        $entity_identifier_name = $input->getArgument('entity-identifier-name');
        $content = str_replace(
            [
                '%api_name%',
                '%serviceName%',
                '%version%',
                '%route_basepath%',
                '%route_identifier_name%',
                '%collection_name%',
                '%entity_identifier_name%',
            ],
            [
                $api_name,
                $serviceName,
                $version,
                $route_basepath,
                $route_identifier_name,
                $collection_name,
                $entity_identifier_name,
            ],
            $template
        );
        $camelCaseToDash = new CamelCaseToDash();
        $configFile = sprintf('api.rest.%s.config.php', $camelCaseToDash->filter($serviceName));
        $path = getcwd();
        file_put_contents($path, $content);
    }

    /**
     * @return string
     */
    protected function getTemplate()
    {
        return file_get_contents(realpath(__DIR__) . '/../../tpl/api.rest.service.config.template');
    }

}
