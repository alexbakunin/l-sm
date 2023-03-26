<?php

namespace App\Services\Author24\Exception;

class QueryErrorException extends \Exception
{
    // Redefine the exception so message isn't optional
    private string $query;

    public function __construct($message, $query = '', $code = 0, \Throwable $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
        $this->query = $query;
    }

    // custom string representation of object
    public function __toString()
    {
        return __CLASS__ . " " . __METHOD__ . ": [" . $this->code . "]: " . $this->message . "\nQuery: " . $this->query;
    }

    public function customMessage()
    {
        echo "A custom function for this type of exception\n";
    }
}
