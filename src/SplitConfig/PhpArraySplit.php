<?php
/**
 *
 * apigility-tools (https://github.com/fabiopellati/apigility-tools)
 *
 * @link      https://github.com/fabiopellati/apigility-tools for the canonical source repository
 * @copyright Copyright (c) 2017 Fabio Pellati (https://github.com/fabiopellati)
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 *
 */

namespace ApigilityTools\Cli\SplitConfig;

use Zend\Stdlib\ArrayUtils;

class PhpArraySplit
    extends \Zend\Config\Writer\PhpArray
{

    public function toFile($filename, $config, $exclusiveLock = true)
    {
        $splitServices = $this->splitServices($config, $filename);
        foreach ($splitServices as $splitKey => $splitService) {
            $splittedFileName = $this->splittedFileName($filename, $splitKey);
            if ($splittedFileName) {
                $this->ensureDirectoryExists($splittedFileName);
                parent::toFile($splittedFileName, $splitService, $exclusiveLock);
            }
        }
    }

    /**
     * prepara il nome del file contenente la configurazione su autoload
     *
     * @param $filename
     * @param $splitKey
     *
     * @return string
     */
    protected function splittedFileName($filename, $splitKey)
    {
        $explodeFileName = explode('/', $filename);
        unset($explodeFileName[count($explodeFileName) - 1]);
        if ($explodeFileName[count($explodeFileName) - 1] != 'autoload') {
            $explodeFileName[] = 'autoload';
        } else {
            return false;
        }
        $explodeFileName[] = 'apigility-split-config';
        $explodeFileName[] = $splitKey . '.config.php';
        $splittedFileName = implode('/', $explodeFileName);

//print_r([__METHOD__=>__LINE__,$splittedFileName]);exit;
        return $splittedFileName;
    }

    /**
     * separa la configurazione per servizi
     *
     * @param $config
     *
     * @return array
     */
    protected function splitServices($config)
    {
        $services = [];
        $routes = (empty($config['router']['routes']))
            ? []
            : $config['router']['routes'];
        foreach ($routes as $routeKey => $route) {

            $services[$routeKey]['router']['routes'][$routeKey] = $route;
            $this->setZfVersioning($routeKey, $services);
            $controller = $route['options']['defaults']['controller'];
            list($entitiesRest, $collectionsRest, $serviceNamesRest, $controllersRest) =
                $this->setZfRest($config, $controller, $services, $routeKey);
            list($serviceNamesRpc, $controllersRpc) = $this->setZfRpc($config, $controller,
                                                                      $services, $routeKey);
            $controllers = ArrayUtils::merge($controllersRest, $controllersRpc, true);
            $this->setZfContentNegotiation($config, $controller, $services, $routeKey);
            $this->setZfHal($config, $controller, $services, $routeKey, $entitiesRest, $collectionsRest);
            $this->setControllers($config, $controller, $services, $routeKey, $controllers);
            $this->setZfMvcAuth($config, $controller, $services, $routeKey, $controllers);
            $inputFilters = $this->setZfContentValidation($config, $controller, $services, $routeKey, $controllers);
            $this->setInputFilterSpecs($config, $controller, $services, $routeKey, $inputFilters);
            $this->setServiceManager($config, $services);
        }

        return $services;

    }

    /**
     * @param $config
     * @param $services
     *
     * @return mixed
     */
    protected function setServiceManager($config, &$services)
    {
        $serviceManager = (empty($config['service_manager']))
            ? []
            : $config['service_manager'];

        $services['service-manager']['service_manager']=$serviceManager;
        return $services;
    }

    /**
     * @param $routeKey
     * @param $services
     *
     * @return mixed
     */
    protected function setZfVersioning($routeKey, &$services)
    {
        if (!empty($services[$routeKey]['zf-versioning']['uri'])) {
            foreach ($services[$routeKey]['zf-versioning']['uri'] as $key => $service) {
                if ($key != $service) {
                    $services[$routeKey]['zf-versioning']['uri'] = [$service => $service];
                    unset($services[$routeKey]['zf-versioning']['uri'][$key]);
                }
            }
        }
        $services[$routeKey]['zf-versioning']['uri'][$routeKey] = $routeKey;

        return $services;
    }

    /**
     * verifica se due controller sono versioni distinte dello stesso controller.
     * per la specifica fare riferimento a
     *
     * @see \ZF\Versioning\VersionListener: onRoute
     *
     * @param $controller
     * @param $sectionKey
     *
     * @return int
     */
    protected function isVersionOfController($controller, $sectionKey)
    {

        $segments = explode('\\', $controller);
        $version = $segments[1];
        if (preg_match('#V.#', $version)) {
            $segments[1] = '(V.)';
        }
        $patternController = implode('\\', $segments);
        $sectionPattern = explode('(V.)', $patternController);
        $pattern = '#' . preg_quote($sectionPattern[0]) . '(V.)' . preg_quote($sectionPattern[1]) . '#';
        $isVersionOfController = preg_match($pattern, $sectionKey);

        return $isVersionOfController;
    }

    /**
     *
     *
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     *
     * @return array
     */
    protected function setZfRest($config, $controller, &$services, $routeKey)
    {

        $entities = [];
        $collections = [];
        $serviceNames = [];
        $controllers = [];
        $zfRest = (empty($config['zf-rest']))
            ? []
            : $config['zf-rest'];
        foreach ($zfRest as $sectionKey => $sectionConfig) {
            $isVersionOfController = $this->isVersionOfController($controller, $sectionKey);
            if ($isVersionOfController) {
                if (!empty($sectionConfig['entity_class'])) {
                    $entities[] = $sectionConfig['entity_class'];
                }
                if (!empty($sectionConfig['collection_class'])) {
                    $collections[] = $sectionConfig['collection_class'];
                }
                $serviceNames[] = $sectionConfig['service_name'];
                $controllers[] = $sectionKey;
                $services[$routeKey]['zf-rest'][$sectionKey] = $sectionConfig;
            };
        }

        return [$entities, $collections, $serviceNames, $controllers];
    }

    /**
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     *
     * @return array
     */
    protected function setZfRpc($config, $controller, &$services, $routeKey)
    {
        $serviceNames = [];
        $controllers = [];
        $zfRpc = (empty($config['zf-rpc']))
            ? []
            : $config['zf-rpc'];
        foreach ($zfRpc as $sectionKey => $sectionConfig) {
            $isVersionOfController = $this->isVersionOfController($controller, $sectionKey);
            if ($isVersionOfController) {
                $serviceNames[] = $sectionConfig['service_name'];
                $controllers[] = $sectionKey;
                $services[$routeKey]['zf-rpc'][$sectionKey] = $sectionConfig;
            };
        }

        return [$serviceNames, $controllers];
    }

    /**
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     *
     */
    protected function setZfContentNegotiation($config, $controller, &$services, $routeKey)
    {

        $controllers = (empty($config['zf-content-negotiation']['controllers']))
            ? []
            : $config['zf-content-negotiation']['controllers'];
        foreach ($controllers as $sectionKey => $sectionConfig) {
            $isVersionOfController = $this->isVersionOfController($controller, $sectionKey);
            if ($isVersionOfController) {
                $services[$routeKey]['zf-content-negotiation']['controllers'][$sectionKey] = $sectionConfig;
            };
        }
        $acceptWhitelist = (empty($config['zf-content-negotiation']['accept_whitelist']))
            ? []
            : $config['zf-content-negotiation']['accept_whitelist'];
        foreach ($acceptWhitelist as $sectionKey => $sectionConfig) {
            $isVersionOfController = $this->isVersionOfController($controller, $sectionKey);
            if ($isVersionOfController) {
                $services[$routeKey]['zf-content-negotiation']['accept_whitelist'][$sectionKey] = $sectionConfig;
            };
        }
        $contentTypeWhitelist = (empty($config['zf-content-negotiation']['content_type_whitelist']))
            ? [] :
            $config['zf-content-negotiation']['content_type_whitelist'];
        foreach ($contentTypeWhitelist as $sectionKey => $sectionConfig) {
            $isVersionOfController = $this->isVersionOfController($controller, $sectionKey);
            if ($isVersionOfController) {
                $services[$routeKey]['zf-content-negotiation']['content_type_whitelist'][$sectionKey] = $sectionConfig;
            };
        }

    }

    /**
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     * @param $entities
     * @param $collections
     *
     */
    protected function setZfHal($config, $controller, &$services, $routeKey, $entities, $collections)
    {
        $metadataMap = (empty($config['zf-hal']['metadata_map'])) ? [] : $config['zf-hal']['metadata_map'];
        foreach ($metadataMap as $sectionKey => $sectionConfig) {
            if (in_array($sectionKey, $entities) || in_array($sectionKey, $collections)) {
                $services[$routeKey]['zf-hal']['metadata_map'][$sectionKey] = $sectionConfig;
            };
        }

    }

    /**
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     * @param $controllers
     *
     */
    protected function setControllers($config, $controller, &$services, $routeKey, $controllers)
    {
        $factories = (empty($config['controllers']['factories'])) ? [] : $config['controllers']['factories'];
        foreach ($factories as $sectionKey => $sectionConfig) {
            if (in_array($sectionKey, $controllers)) {
                $services[$routeKey]['controllers']['factories'][$sectionKey] = $sectionConfig;
            };
        }

    }

    /**
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     * @param $controllers
     *
     */
    protected function setZfMvcAuth($config, $controller, &$services, $routeKey, $controllers)
    {
        $authorization =
            (empty($config['zf-mvc-auth']['authorization'])) ? [] : $config['zf-mvc-auth']['authorization'];
        foreach ($authorization as $sectionKey => $sectionConfig) {
            if (in_array($sectionKey, $controllers)) {
                $services[$routeKey]['zf-mvc-auth']['authorization'][$sectionKey] = $sectionConfig;
            };
        }

    }

    /**
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     * @param $controllers
     *
     * @return array
     */
    protected function setZfContentValidation($config, $controller, &$services, $routeKey, $controllers)
    {
        $inputFilters = [];
        $zfContentValidation = (empty($config['zf-content-validation'])) ? [] : $config['zf-content-validation'];
        foreach ($zfContentValidation as $sectionKey => $sectionConfig) {
            if (in_array($sectionKey, $controllers)) {
                $inputFilters[] = $sectionConfig['input_filter'];
                $services[$routeKey]['zf-content-validation'][$sectionKey] = $sectionConfig;
            };
        }

        return $inputFilters;

    }

    /**
     * @param $config
     * @param $controller
     * @param $services
     * @param $routeKey
     * @param $inputFilters
     *
     */
    protected function setInputFilterSpecs($config, $controller, &$services, $routeKey, $inputFilters)
    {

        $inputFilterSpecs = (empty($config['input_filter_specs'])) ? [] : $config['input_filter_specs'];
        $services[$routeKey]['input_filter_specs'] = [];
        foreach ($inputFilterSpecs as $sectionKey => $sectionConfig) {
            $inputsName = [];
            foreach ($sectionConfig as $key => $item) {
                $inputsName[$item['name']] = $item;
            }
            $sectionConfigNew = [];
            foreach ($inputsName as $item) {
                $sectionConfigNew[] = $item;
            }
            if (in_array($sectionKey, $inputFilters)) {
                $services[$routeKey]['input_filter_specs'][$sectionKey] = $sectionConfigNew;
            };
        }

    }

    /**
     * se la path non esiste sul filesystem, la genera
     *
     * @param $splittedFileName
     */
    protected function ensureDirectoryExists($splittedFileName)
    {
        $parts = explode('/', $splittedFileName);
        $file = array_pop($parts);
        $dir = '';
        foreach ($parts as $part) {
            if (!is_dir($dir .= "/$part")) {
                mkdir($dir);
            }
        }
    }

}
