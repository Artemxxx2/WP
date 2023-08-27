<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\AbstractHttpResponse;
use SabaiApps\Framework\Application\Context as FrameworkContext;
use SabaiApps\Framework\Application\HttpContext as FrameworkHttpContext;

class Response extends AbstractHttpResponse
{
    const ERROR_BAD_REQUEST = 400, ERROR_UNAUTHORIZED = 401, ERROR_FORBIDDEN = 403,
        ERROR_NOT_FOUND = 404, ERROR_METHOD_NOT_ALLOWED = 405, ERROR_NOT_ACCEPTABLE = 406, ERROR_VALIDATE_FORM = 422,
        ERROR_INTERNAL_SERVER_ERROR = 500, ERROR_NOT_IMPLEMENTED = 501, ERROR_SERVICE_UNAVAILABLE = 503,
        REDIRECT_PERMANENT = 301, REDIRECT_TEMPORARY = 302, REDIRECT_HTML = 200;
    const PARAM_TIMESTAMP = '_ts_';

    private $_layoutHtmlTemplate, $_inlineLayoutHtmlTemplate, $_redirectUrl, $_redirectType;

    /**
     * Call an application helper
     */
    public function __call($name, $args)
    {
        return $this->_application->callHelper($name, $args);
    }

    public function send(FrameworkContext $context)
    {
        $this->_application->Action('core_response_send', [$context, $this]);

        $ret = parent::send($context);

        $this->_application->Action('core_response_send_complete', [$context]);

        return $ret;
    }

    public function setInlineLayoutHtmlTemplate($template)
    {
        $this->_inlineLayoutHtmlTemplate = $template;

        return $this;
    }

    public function setLayoutHtmlTemplate($template)
    {
        $this->_layoutHtmlTemplate = $template;

        return $this;
    }

    protected function _getSuccessUrl(Context $context, $separator = '&')
    {
        if (!$url = $context->getSuccessUrl()) {
            if (!isset($url)) {
                $url = Request::url(); // use the current URL
            }
        } else {
            $url = $this->_application->Url($url); // converts to an SabaiApps\Framework\Application\URL object
            $url['separator'] = $separator;
            if (Request::isXhr()) {
                $url['params'] += [self::PARAM_TIMESTAMP => time()]; // prevent cache
            }
        }

        return $url;
    }

    protected function _getRedirectUrl(Context $context, $separator = '&')
    {
        if (!$url = $context->getRedirectUrl()) {
            $url = Request::url(); // use the current URL
        } else {
            $url = $this->_application->Url($url); // converts to an SabaiApps\Framework\Application\URL object
            $url['separator'] = $separator;
            if (Request::isXhr()) {
                $url['params'] += [self::PARAM_TIMESTAMP => time()]; // prevent cache
            }
        }

        return $url;
    }

    protected function _sendSuccess(FrameworkContext $context)
    {
        $success_url = (string)$this->_getSuccessUrl($context);
        if ($context->getRequest()->isAjax()) {
            if ($success_url) {
                $messages = null;
            } else {
                $context->setFlashEnabled(false);
                $messages = $context->getFlash();
            }

            // Send success response as json
            if (!headers_sent()) {
                if (defined('DRTS_RESPONSE_SUCCESS_HTTP_CODE')
                    && DRTS_RESPONSE_SUCCESS_HTTP_CODE === 200
                ) {
                    $status_code = DRTS_RESPONSE_SUCCESS_HTTP_CODE;
                } else {
                    $status_code = 278;
                }
                self::sendStatusHeader($status_code, 'Success');
                self::sendHeader('Content-type', 'application/json; charset=' . $context->getCharset());
            }
            echo $this->_application->JsonEncode(
                array_merge(
                    $this->_getAttributes($context->getSuccessAttributes()),
                    [
                        'url' => $success_url,
                        'messages' => $messages,
                    ]
                )
            );

            return;
        }

        $this->_sendResults($context, $success_url);
    }

    public function getError(FrameworkContext $context)
    {
        $url = $context->getErrorUrl();
        $default_message = '';
        switch ($context->getErrorType()) {
            case self::ERROR_UNAUTHORIZED:
                $url = $this->_application->LoginUrl((string)$this->_application->Url($url));
                break;
            case self::ERROR_NOT_FOUND:
                $default_message = __('The requested page was not found.', 'directories');
                break;
            default:
                $default_message = __('The server encountered an error processing your request.', 'directories');
        }

        // Use default error message if none set
        if (null === $message = $context->getErrorMessage()) {
            $message = $default_message;
        }

        // Always convert to URL object
        if (isset($url)) {
            $url = $this->_application->Url($url);
        } else {
            if ((string)$context->getRoute() === '/') {
                // An error occurred on the top page. Throw an exception to prevent redirection loop.
                throw new \RuntimeException(__('The server encountered an error processing your request.', 'directories'));
            }
            $url = $this->_application->Url(); // redirect to the top page
        }

        return ['url' => (string)$url, 'message' => $message];
    }

    protected function _sendError(FrameworkContext $context)
    {
        $error = $this->getError($context);
        $context->addFlash($error['message'], 'danger');

        if ($context->getRequest()->isAjax()) {
            $context->setFlashEnabled(false);
            $messages = $context->getFlash();

            // Send error response as json
            if (!headers_sent()) {
                self::sendStatusHeader($context->getErrorType());
                self::sendHeader('Content-type', 'application/json; charset=' . $context->getCharset());
            }
            echo $this->_application->JsonEncode(
                array_merge(
                    $this->_getAttributes($context->getErrorAttributes()),
                    [
                        'messages' => $messages,
                        'url' => (string)$error['url'],
                    ]
                )
            );

            return;
        }

        $this->_sendResults($context, $error['url']);
    }

    protected function _sendResults(Context $context, $url)
    {
        if (headers_sent()) {
            $context->setFlashEnabled(false);
            $html = [];
            foreach ($context->getFlash() as $_flash) {
                if (!strlen($_flash['msg'])) continue;

                $html[] = '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-' . $_flash['level'] . '">' . $this->_application->H($_flash['msg']) . '</div>';
            }
            $timeout = 3000;
            if ($context->isError()) {
                switch ($context->getErrorType()) {
                    case self::ERROR_UNAUTHORIZED:
                        break;
                    case self::ERROR_NOT_FOUND:
                        $timeout = 5000;
                        break;
                    default:
                        $timeout = 30000;
                }
            }
            echo $this->_application->RedirectHtml($url, implode(PHP_EOL, $html), $timeout);
        } else {
            $this->_doSendRedirect($url);
        }
    }

    protected function _getAttributes(array $attributes)
    {
        foreach (array_keys($attributes) as $k) {
            if ($attributes[$k] instanceof \SabaiApps\Framework\Application\Url) {
                $attributes[$k]['separator'] = '&';
                $attributes[$k] = (string)$attributes[$k];
            }
        }
        return $attributes;
    }

    protected function _sendView(FrameworkContext $context)
    {
        $this->_application->Action('core_response_send_view', [$context]);
        // Invoke controller specific event
        $action = 'core_response_send_view_' . $context->getRoute()->getComponent() . '_';
        if ($this->_application->getPlatform()->isAdmin()) {
            $action .= 'admin_';
        }
        $action .= $context->getRoute()->getControllerName();
        $this->_application->Action(strtolower($action), [$context]);

        switch ($context->getContentType()) {
            case 'xml':
                if (!headers_sent()) {
                    self::sendStatusHeader(200);
                    self::sendHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
                    self::sendHeader('Content-Type', sprintf('text/xml; charset=%s', $context->getCharset()));
                    $this->_sendHeaders();
                }
                $this->_sendXmlView($context);
                return;
            case 'json':
                if (!headers_sent()) {
                    self::sendStatusHeader(200);
                    self::sendHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
                    self::sendHeader('Content-Type', 'application/json');
                    $this->_sendHeaders();
                }
                $this->_sendJsonView($context);
                return;
            default:
                if (!headers_sent()) {
                    self::sendStatusHeader(200);
                    self::sendHeader('Content-Type', sprintf('text/html; charset=%s', $context->getCharset()));
                    $this->_sendHeaders();
                }
                $this->_sendHtmlView($context);
        }
    }

    private function _sendXmlView(Context $context)
    {
        echo '<?xml version="1.0" encoding="' . $this->_application->H($context->getCharset()) . '"?>';

        $this->_application->getTemplate($context)->display(
            array_reverse($context->getTemplates()),
            ['CONTEXT' => $context] + $context->getAttributes(),
            '.xml'
        );
    }

    private function _sendJsonView(Context $context)
    {
        $this->_application->getTemplate($context)->display(
            array_reverse($context->getTemplates()),
            ['CONTEXT' => $context] + $context->getAttributes(),
            '.json'
        );
    }

    private function _sendHtmlView(Context $context)
    {
        $template = $this->_application->getTemplate($context);

        // Fetch content
        $content = $template->render(
            array_reverse($context->getTemplates()),
            ['CONTEXT' => $context] + $context->getAttributes(),
            '.' . $context->getContentType()
        );

        // No layout if the requested content is an HTML fragment
        if (!isset($this->_inlineLayoutHtmlTemplate) && !isset($this->_layoutHtmlTemplate)) {
            // No layout templates, so output content directly
            echo $content;
            return;
        }

        $vars = ['CONTENT' => $content, 'CONTEXT' => $context];

        $this->_application->Action('core_response_send_view_layout', [$context, &$content, &$vars]);

        // Add inline layout?
        if (isset($this->_inlineLayoutHtmlTemplate)) {
            if (!isset($this->_layoutHtmlTemplate)) {
                // No layout template, so output content directly
                $this->_include($this->_inlineLayoutHtmlTemplate, $vars);
                return;
            }
            // Fetch content with inline layout
            ob_start();
            $this->_include($this->_inlineLayoutHtmlTemplate, $vars);
            $vars['CONTENT'] = ob_get_clean();
        }

        $this->_include($this->_layoutHtmlTemplate, $vars);
    }

    private function _include()
    {
        extract(func_get_arg(1), EXTR_SKIP);
        return include func_get_arg(0);
    }

    protected function _sendRedirect(FrameworkHttpContext $context)
    {
        $url = (string)$this->_getRedirectUrl($context);
        if (headers_sent()) {
            echo $this->_application->RedirectHtml($url);
        } else {
            $this->_doSendRedirect($url, $context->getRedirectType());
        }
    }

    protected function _doSendRedirect($url, $type = self::REDIRECT_TEMPORARY)
    {
        self::sendStatusHeader($type);
        self::sendHeader('Location', $url);
        // Prevent redirection from being cached by browser
        self::sendHeader('Cache-Control', 'no-store, must-revalidate');
        self::sendHeader('Expires', 0);
    }
}
