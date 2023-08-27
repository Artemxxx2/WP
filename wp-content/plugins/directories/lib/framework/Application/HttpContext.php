<?php
namespace SabaiApps\Framework\Application;

class HttpContext extends Context
{
    const STATUS_REDIRECT = 4;

    protected $_charset = 'UTF-8', $_contentType, $_redirectUrl;

    public function setRedirect($redirectUrl)
    {
        $this->_status = self::STATUS_REDIRECT;
        $this->_redirectUrl = $redirectUrl;

        return $this;
    }

    public function isRedirect()
    {
        return $this->_status === self::STATUS_REDIRECT;
    }

    public function getRedirectUrl()
    {
        return $this->_redirectUrl;
    }

    public function getCharset()
    {
        return $this->_charset;
    }

    public function setCharset($charset)
    {
        $this->_charset = $charset;

        return $this;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }

    public function setContentType($contentType)
    {
        $this->_contentType = $contentType;

        return $this;
    }
}