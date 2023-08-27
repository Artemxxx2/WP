<?php
namespace SabaiApps\Framework\Application;

abstract class AbstractHttpResponse extends AbstractResponse
{
    private static $_codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    protected $_headers = [];

    public function setHeader($name, $value)
    {
        $this->_headers[$name] = $value;

        return $this;
    }

    public function hasHeader($name)
    {
        return isset($this->_headers[$name]);
    }

    public function send(Context $context)
    {
        switch ($context->getStatus()) {
            case Context::STATUS_SUCCESS:
                return $this->_sendSuccess($context);
            case Context::STATUS_ERROR:
                return $this->_sendError($context);
            case HttpContext::STATUS_REDIRECT:
                return $this->_sendRedirect($context);
            default:
                return $this->_sendView($context);
        }
    }

    protected function _sendRedirect(HttpContext $context)
    {
        self::sendHeader('Location', $context->getRedirectUrl());
    }

    protected function _sendHeaders()
    {
        foreach ($this->_headers as $name => $value) {
            self::sendHeader($name, $value);
        }
    }

    public static function sendHeader($name, $value)
    {
        header(str_replace(["\r", "\n"], '', $name . ': ' . $value));
    }

    public static function sendStatusHeader($code, $message = null)
    {
        if (!isset(self::$_codes[$code])) {
            // Custom status code requires status message
            if (!isset($message)) return;
        } else {
            $message = self::$_codes[$code];
        }
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';  
        if (!headers_sent()) {
            header(sprintf('%s %d %s', $protocol, $code, $message), true, $code);
        }
    }
}