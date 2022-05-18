<?php
/**
 * Created by PhpStorm.
 * User: fabio
 * Date: 27/02/18
 * Time: 15.42
 */

namespace ApigilityTools\Cli;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

trait GetInteractiveParamsTrait
{

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return array
     */
    protected function getApiName(InputInterface $input, OutputInterface $output)
    {
        $camelcaseValidator = function ($answer) use ($output) {
            if (preg_match('#[-_\.,\s]#', $answer)) {
                throw new \RuntimeException($answer . ' invalid: must be CamelCase');
            }

            return $answer;
        };
        $value = $this->getOptionalArgumentValue('api-name', $input, $output, $camelcaseValidator);

        return $value;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return array
     */
    protected function getServiceName(InputInterface $input, OutputInterface $output)
    {
        $camelcaseValidator = function ($answer) use ($output) {
            if (preg_match('#[-_\.,\s]#', $answer)) {
                throw new \RuntimeException($answer . ' invalid: must be CamelCase');
            }

            return $answer;
        };
        $value = $this->getOptionalArgumentValue('service-name', $input, $output, $camelcaseValidator);

        return $value;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return array
     */
    protected function getLeftServiceName(InputInterface $input, OutputInterface $output)
    {
        $camelcaseValidator = function ($answer) use ($output) {
            if (preg_match('#[-_\.,\s]#', $answer)) {
                throw new \RuntimeException($answer . ' invalid: must be CamelCase');
            }

            return $answer;
        };
        $value = $this->getOptionalArgumentValue('left-service-name', $input, $output, $camelcaseValidator);

        return $value;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return array
     */
    protected function getRightServiceName(InputInterface $input, OutputInterface $output)
    {
        $camelcaseValidator = function ($answer) use ($output) {
            if (preg_match('#[-_\.,\s]#', $answer)) {
                throw new \RuntimeException($answer . ' invalid: must be CamelCase');
            }

            return $answer;
        };
        $value = $this->getOptionalArgumentValue('right-service-name', $input, $output, $camelcaseValidator);

        return $value;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return string
     */
    protected function getApiRoot(InputInterface $input, OutputInterface $output)
    {
        $value = $this->getOptionalArgumentValue(
            'api-root',
            $input,
            $output,
            function ($answer) use ($output) {
                $apiRoot = sprintf('./data/%s', $answer);
                $output->writeLn(sprintf('api-root: %s', $apiRoot));
                if (!is_dir($apiRoot) || !is_writable($apiRoot)) {
                    throw new \RuntimeException(sprintf('The "%s" is not a directory or is not writable', $answer));
                }

                return $apiRoot;
            }
        );
                $apiRoot = sprintf('./data/%s', $value);
        return $apiRoot;
    }

    /**
     * @param                                                   $argument
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @param null                                              $validator
     * @param null                                              $attemp
     *
     * @return array
     */
    protected function getOptionalArgumentValue(
        $argument,
        InputInterface $input,
        OutputInterface $output,
        $validator = null,
        $attemp = null,
        $default = null
    ) {
        $value = $input->getArgument($argument);
        while (empty($value)) {
            $question = new Question(
                sprintf('<question>%s [%s]:</question> ', $argument, $default),
                $default
            );
            if (!empty($validator)) {
                $question->setValidator($validator);
            }
            $question->setMaxAttempts($attemp);
            $value = $this->getHelper('question')->ask($input, $output, $question);
        }

        return $value;
    }

    /**
     * @param                                                   $option
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @param null                                              $validator
     * @param int                                               $attemp
     *
     * @return array
     */
    protected function getOptionalOptionValue(
        $option,
        InputInterface $input,
        OutputInterface $output,
        $validator =
        null,
        $attemp = 1,
        $default = null
    ) {
        $value = $input->getOption($option);
        while (empty($value) && $value !== $default) {
            $question = new Question(
                sprintf('<question>%s [%s]:</question> ', $option, $default),
                $default
            );
            if (!empty($validator)) {
                $question->setValidator($validator);
            }
            $question->setMaxAttempts($attemp);
            $value = $this->getHelper('question')->ask($input, $output, $question);
        }

        return $value;
    }
}
