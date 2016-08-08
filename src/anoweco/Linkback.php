<?php
namespace anoweco;

class Linkback
{
    protected $lbc;

    protected function initLbc()
    {
        $this->lbc = new \PEAR2\Services\Linkback\Client();
        $req = $this->lbc->getRequest();
        /*
        $req->setConfig(
            array(
                'ssl_verify_peer' => false,
                'ssl_verify_host' => false
            )
        );
        */
        $headers = $req->getHeaders();
        $req->setHeader('user-agent', 'anoweco');
        $this->lbc->setRequestTemplate($req);
    }

    public function ping($postId)
    {
        $this->initLbc();
        $storage = new Storage();
        $rowPost = $storage->getJsonComment($postId)->Xrow;

        $from = Urls::full(Urls::comment($postId));
        $to   = $rowPost->comment_of_url;

        try {
            $res = $this->lbc->send($from, $to);
            if (!$res->isError()) {
                //all ok
                $error = false;
            } else {
                //some error
                error_log($res->getMessage());
                $error = true;
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $error = true;
        }

        if ($error) {
            $pingState = $rowPost->comment_pingstate + 1;
        } else {
            $pingState = 'ok';
        }
        $storage->setPostPingState($postId, $pingState);
    }
}
?>
