<?php

namespace vpashkov\represent;


use yii\base\Exception;

class RepresentQueryException extends Exception
{

    private $represent;
    private $needle;

    /**
     * RepresentQueryException constructor.
     * @param string $message
     * @param Represent $represent
     * @param string $needle
     */
    public function __construct($message = "", $represent, $needle)
    {
        parent::__construct($message, 0, null);

        $this->represent = $represent;
        $this->needle = $needle;

        \Yii::$app->errorHandler->exceptionView = '@vpashkov/represent/views/queryException.php';
    }

    public function renderQuery()
    {
        $lines = $this->createLines($this->represent->rawMap);
        $line = $this->findError($lines, $this->needle);

        return \Yii::$app->errorHandler->renderFile('@vpashkov/represent/views/query.php', [
            'class' => get_class($this->represent),
            'line' => $line,
            'lines' => $lines,
        ]);
    }

    public function findError($lines, $needle)
    {
        for ($i = 0; $i < count($lines); $i++) {
            if (mb_strpos($lines[ $i ], $needle) !== false) {
                return $i;
            }
        }
        return 0;
    }

    public function createLines($rawMap, $tab = '')
    {
        $lines = [];

        foreach ($rawMap as $key => $value) {
            if (is_numeric($key)) {
                $lines[] = $tab . $value . ",\n";
            }
            if (is_string($key) && mb_strpos($key, '#') === 0) {
                $lines[] = $tab . $key . ' => ' . $this->configToLine($value) . ",\n";
            }
            if (is_string($key) && is_array($rawMap) && mb_strpos($key, '#') === false) {
                $lines[] = $tab . "$key => [ \n";
                $lines = array_merge($lines, $this->createLines($value, $tab . "\t"));
                $lines[] = $tab . "] \n";
            }
        }
        return $lines;
    }

    public function configToLine($config)
    {
        if (is_string($config)) {
            return $config;
        }
        if (is_array($config)) {
            $line = "[ ";
            foreach ($config as $key => $value) {
                $line .= "$key => $value, ";
            }
            $line .= ']';
            return $line;
        }
        return "";
    }

}