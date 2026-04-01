<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sandbox\UnsafeSandbox;

class UnsafeSandbox implements \Core\Sandbox\SandboxInterface
{
    public function execute(\Core\Sandbox\SandboxSubject $subject, \Core\Sandbox\SandboxContext $sandboxContext)
    {
        $response = \Traffic\Response\Response::buildHtml();
        extract($sandboxContext->asHash());
        ob_start();
        if ($subject->filePath()) {
            include $subject->filePath();
        } else {
            $code = $this->_applyPatchesForCode($subject->code());
            eval($code);
        }
        $body = ob_get_contents();
        ob_end_clean();
        if ($subject->layoutPath()) {
            $newContext = clone $sandboxContext;
            $newContext->content = $body;
            $wrapperSubject = \Core\Sandbox\SandboxSubject::fromFile($subject->layoutPath());
            return $this->execute($wrapperSubject, $newContext);
        }
        $response = $response->withBody(\Traffic\Response\ResponseFactory::safeBody($body));
        return $response;
    }
    private function _applyPatchesForCode($code)
    {
        $code = "<?php ?>" . $code;
        return $code;
    }
}

?>