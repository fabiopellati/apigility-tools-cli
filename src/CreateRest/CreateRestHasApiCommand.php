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
use Zend\Filter\Word\CamelCaseToUnderscore;

class CreateRestHasApiCommand
    extends Command
{
    use GetInteractiveParamsTrait;
    const HELP = '';
    const HELP_ARGS = [
        'api-name'               => 'api name the last segment of api namespace',
        'left-service-name'      => 'left side service name',
        'right-service-name'     => 'right side service name',
        'api-root'               => 'root path of api module',
        'api-version'            => 'apigility api version',
        'route-basepath'         => '',
        'route-identifier-name'  => 'apigility config params',
        'collection-name'        => 'apigility config params',
        'entity-identifier-name' => 'apigility config params',
    ];

    protected function configure()
    {
        $this->setDescription('Create an apigility-tools standard rest service.');
        $this->setHelp(self::HELP);
        $this->addArgument('api-name', InputArgument::OPTIONAL, self::HELP_ARGS['api-name']);
//        $this->addArgument('service-name', InputArgument::REQUIRED, self::HELP_ARGS['service-name']);
        $this->addArgument('left-service-name', InputArgument::OPTIONAL, self::HELP_ARGS['left-service-name']);
        $this->addArgument('right-service-name', InputArgument::OPTIONAL, self::HELP_ARGS['right-service-name']);
        $this->addArgument('api-root', InputArgument::OPTIONAL, self::HELP_ARGS['api-root']);
        $this->addOption('api-version', null, InputArgument::OPTIONAL, self::HELP_ARGS['api-version']);
        $this->addOption('route-basepath', null, InputArgument::OPTIONAL, self::HELP_ARGS['route-basepath']);
        $this->addOption('route-identifier-name', null, InputArgument::OPTIONAL,
                         self::HELP_ARGS['route-identifier-name']);
//        $this->addOption('collection-name', null, InputArgument::OPTIONAL, self::HELP_ARGS['collection-name']);
        $this->addOption('entity-identifier-name', null, InputArgument::OPTIONAL,
                         self::HELP_ARGS['entity-identifier-name'] );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $camelCaseToUnderscore = new CamelCaseToUnderscore();
            $apiName = $this->getApiName($input, $output);
            $apiRoot = $this->getApiRoot($input, $output);
            $leftServiceName = $this->getLeftServiceName($input, $output);
            $rightServiceName = $this->getRightServiceName($input, $output);
            $version = $this->getOptionalOptionValue('version', $input, $output, null,1, 'v1');
            $route_basepath = $this->getOptionalOptionValue('route-basepath', $input, $output, null, 1, '');
            $route_basepath = (!empty($route_basepath)) ? $route_basepath . '/' : $route_basepath;
            $route_identifier_name = $this->getOptionalOptionValue('route-identifier-name', $input, $output, null, 1,
                                                                   strtolower($camelCaseToUnderscore->filter
                                                                              ($leftServiceName) .
                                                                              '_id'));
//            $collection_name = $input->getOption('collection-name') ?: strtolower($camelCaseToUnderscore->filter
//            ($serviceName));
            $entity_identifier_name = $this->getOptionalOptionValue('entity-identifier-name', $input, $output,
                                                                    null, 1,
                                                                    strtolower($camelCaseToUnderscore->filter($rightServiceName)
                                                                    .'_id'));

            $output->writeln('<comment>eseguito con i parametri:</comment>');
            $output->writeln(sprintf(
                                 '<comment> create.rest.api '.
                                 ' --api-version=%s --route-basepath=%s --route-identifier-name=%s'.
                                 ' --entity-identifier-name=%s '.
                                 ' %s %s %s %s </comment>',
                                 $version, $route_basepath, $route_identifier_name,
                                 $entity_identifier_name,
                                 $apiName, $leftServiceName, $rightServiceName, $apiRoot
                             ));


            $this->writeConfig($apiName, $leftServiceName, $rightServiceName, $version, $route_basepath,
                               $entity_identifier_name, $apiRoot);
            $this->writeHalLinkListenerClass($apiName, $leftServiceName, $rightServiceName, $version, $route_basepath,
                                             $entity_identifier_name,$route_identifier_name, $apiRoot);
            $this->writeEntityClass($apiName, $leftServiceName . 'Has' . $rightServiceName, $version, $apiRoot);
            $this->writeCollectionClass($apiName, $leftServiceName . 'Has' . $rightServiceName, $version, $apiRoot);

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
        $split = "$autoload/apigility-split-config";
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
     * @param $version
     * @param $route_basepath
     * @param $entity_identifier_name
     * @param $apiRoot
     */
    protected function writeConfig($apiName, $leftServiceName, $rightServiceName, $version,
                                   $route_basepath,
                                   $entity_identifier_name, $apiRoot)
    {
        $camelCaseToDash = new CamelCaseToDash();
        $template =
            file_get_contents(realpath(__DIR__ . '/../../tpl/api.rest.left-service_has_right-service.config.template'));
        $values = [
            strtolower($camelCaseToDash->filter($apiName)),
            $apiName,
            strtolower($camelCaseToDash->filter($leftServiceName)),
            $leftServiceName,
            strtolower($camelCaseToDash->filter($rightServiceName)),
            $rightServiceName,
            $version,
            ucfirst($version),
            $route_basepath,
            $entity_identifier_name,
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
            '%route_basepath%',
            '%entity_identifier_name%',
        ];
        $content = str_replace(
            $keys,
            $values,
            $template
        );
        $splitconfigpath = $this->getSplitConfigPath($apiRoot);
        $filename = sprintf('%s.rest.%s_has_%s.config.php', $values[0], $values[2], $values[4]);
        file_put_contents($splitconfigpath . '/' . $filename, $content);
    }


    /**
     * @param $apiName
     * @param $serviceName
     * @param $version
     * @param $apiRoot
     */
    protected function writeEntityClass($apiName, $serviceName, $version, $apiRoot)
    {

        $template = file_get_contents(realpath(__DIR__ . '/../../tpl/ServiceEntity.template'));
        $values = [
            $apiName,
            $serviceName,
            ucfirst($version),
        ];
        $keys = [
            '%apiName%',
            '%serviceName%',
            '%Version%',
        ];
        $content = str_replace(
            $keys,
            $values,
            $template
        );
        $path = $this->getServicePath($values[1], $values[2], $apiRoot);
        $filename = sprintf('%sEntity.php', $values[1]);
        file_put_contents($path . '/' . $filename, $content);
    }

    /**
     * @param $apiName
     * @param $serviceName
     * @param $version
     * @param $apiRoot
     */
    protected function writeCollectionClass($apiName, $serviceName, $version, $apiRoot)
    {

        $template = file_get_contents(realpath(__DIR__ . '/../../tpl/ServiceCollection.template'));
        $values = [
            $apiName,
            $serviceName,
            ucfirst($version),
        ];
        $keys = [
            '%apiName%',
            '%serviceName%',
            '%Version%',
        ];
        $content = str_replace(
            $keys,
            $values,
            $template
        );
        $path = $this->getServicePath($values[1], $values[2], $apiRoot);
        $filename = sprintf('%sCollection.php', $values[1]);
        file_put_contents($path . '/' . $filename, $content);
    }
    /**
     * @param $apiName
     * @param $serviceName
     * @param $version
     * @param $apiRoot
     * @param $entity_identifier_name
     */
    protected function writeHalLinkListenerClass($apiName, $leftServiceName, $rightServiceName, $version,
                                                 $route_basepath,
                                                 $entity_identifier_name, $route_identifier_name,$apiRoot)
    {
        $camelCaseToDash = new CamelCaseToDash();

        $template = file_get_contents(realpath(__DIR__ . '/../../tpl/HasHalLinkListener.template'));
        $values = [
            strtolower($camelCaseToDash->filter($apiName)),
            $apiName,
            strtolower($camelCaseToDash->filter($leftServiceName)),
            $leftServiceName,
            strtolower($camelCaseToDash->filter($rightServiceName)),
            $rightServiceName,
            $version,
            ucfirst($version),
            $route_basepath,
            $entity_identifier_name,
            $route_identifier_name
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
            '%route_basepath%',
            '%entity_identifier_name%',
            '%route_identifier_name%',
        ];
        $content = str_replace(
            $keys,
            $values,
            $template
        );
        $path = $this->getServicePath($values[3].'Has'.$values[5], $values[7],  $apiRoot);
        $filename = 'HalLinkListener.php';
        file_put_contents($path . '/' . $filename, $content);
    }
    /**
     * @param $serviceName
     * @param $version
     * @param $apiRoot
     *
     * @return mixed
     */
    protected function getServicePath($serviceName, $version, $apiRoot)
    {

        $cwd = realpath(getcwd() . '/' . $apiRoot);
        $src = "$cwd/src";
        $v = "$src/$version";
        $rest = "$v/Rest";
        $service = "$rest/$serviceName";
        if (!is_dir($src)) {
            mkdir($src);
        }
        if (!is_dir($v)) {
            mkdir($v);
        }
        if (!is_dir($rest)) {
            mkdir($rest);
        }
        if (!is_dir($service)) {
            mkdir($service);
        }

        return $service;
    }
}
