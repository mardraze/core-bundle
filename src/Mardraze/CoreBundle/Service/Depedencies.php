<?php
/**
 * This source is under Mardraze License
 * http://mardraze.pl/license
 *
 * User: mardraze
 * Date: 09.03.15
 */

namespace Mardraze\CoreBundle\Service;


class Depedencies {


    protected $container;

    public function __construct($container){
        $this->container = $container;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest(){
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    public function getDoctrine(){
        return $this->get('doctrine');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getManager(){
        return $this->getDoctrine()->getManager();
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection(){
        return $this->getDoctrine()->getConnection();
    }

    public function getRepository($str){
        return $this->getDoctrine()->getRepository($this->getRepositoryName($str));
    }

    public function persist($obj){
        $this->getManager()->persist($obj);
        return $this;
    }

    public function flush(){
        $this->getManager()->flush();
        return $this;
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer(){
        return $this->get('swiftmailer.mailer.default');
    }

    public function getParameter($str){
        return $this->container->getParameter($str);
    }

    public function getContainer(){
        return $this->container;
    }

    public function get($str){
        return $this->container->get($str);
    }

    public function getWebDir(){
        return $this->getParameter('kernel.root_dir').'/../web';
    }

    /**
     * @return \Symfony\Bridge\Monolog\Logger
     */
    public function getLogger(){
        return $this->get('logger');
    }

    /**
     * @return \Mardraze\CoreBundle\Translation\Translator
     */
    public function getTranslator(){
        return $this->get('translator');
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher(){
        return $this->get('event_dispatcher');
    }

    /**
     * @return \Mardraze\CoreBundle\Entity\User
     */
    public function getUser(){
        $token = $this->container->get('security.context')->getToken();
        if($token){
            return $token->getUser();
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSession(){
        return $this->container->get('session');
    }


    /**
     * @return \Doctrine\Common\Cache\FilesystemCache
     */
    public function getCache(){
        return $this->container->get('mardraze_core.cache');
    }

    public function setFlash($key, $value){
        $this->getRequest()->getSession()->getFlashBag()->set($key, $value);
    }

    public function getFlash($key){
        $this->getRequest()->getSession()->getFlashBag()->get($key);
    }

    /**
     * @return \Mardraze\CoreBundle\Twig\CoreExtension
     */
    public function getTwigExtensions(){
        return $this->container->get('mardraze_core.twig_extension');
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig(){
        return $this->get('twig');
    }

    /**
     * @param $template
     * @param array $parameters
     * @param null $from
     * @return \Swift_Message
     */
    public function getMessage($templateStr, $parameters = array(), $from = null) {
        $template = $this->getTwig()->loadTemplate($templateStr);
        $subject = $template->renderBlock('subject', $parameters);
        if(!$subject){
            throw new \Exception('Subject is empty');
        }
        $bodyHtml = $template->renderBlock('message_html', $parameters);
        $bodyText = trim($template->renderBlock('message_text', $parameters));
        if(!$from){
            if($this->container->hasParameter('delivery_address')){
                $from = $this->getParameter('delivery_address');
            }else{
                $from = $this->getParameter('mailer_user');
            }
            if($this->getParameter('mailer_from_name')){
                $from = array($from => $this->getParameter('mailer_from_name'));
            }
        }

        $msg = $this->getMailer()->createMessage()
            ->setSubject($subject)
            ->setBody($bodyText, 'text/plain')
            ->setFrom($from)
            ->addPart($bodyHtml, 'text/html')
        ;
        return $msg;
    }

    public function sendEmail($addresses, $template, $parameters = array(), $from = null, $attachments = array()) {
        $msg = $this->getMessage($template, $parameters, $from);
        if(!is_array($addresses)){
            $addresses = array($addresses);
        }
        if($this->container->hasParameter('mailer_replyto')){
            $msg->setReplyTo($this->getParameter('mailer_replyto'));
        }
        $msg->setTo($addresses);
        foreach ($attachments as $attachment) {
            $att = null;
            if(is_array($attachment)){
                $att = \Swift_Attachment::fromPath($attachment['path']);
                $att->setFilename($attachment['name']);
            }else{
                $att = \Swift_Attachment::fromPath($attachment);
            }
            $msg->attach($att);
        }

        return $this->getMailer()->send($msg);
    }


    /**
     * @return \Sonata\NotificationBundle\Backend\RuntimeBackend
     */
    public function getBackend(){
        return $this->get('sonata.notification.backend');
    }

    public function runConsumer($type, array $body){
        return $this->getBackend()->createAndPublish($type, $body);
    }

    public function getPage(){
        $page = $this->getRequest()->get('page', 1);
        if($page <= 1){
            $page = 1;
        }
        return $page;
    }

    public function countRows($table, $where = array()){
        if(!empty($where) && !array_key_exists(0, $where)){
            $where2 = array();
            foreach($where as $k => $v){
                $where2[] = '`'.$k.'`="'.$v.'"';
            }
            $where = $where2;
        }
        $rows = $this->getConnection()->fetchAll('SELECT COUNT(*) as count_all FROM `'.$table.'` '.(empty($where) ? '' : (' WHERE '.implode(' AND ', $where))));
        return $rows[0]['count_all']*1;
    }

    public function mkdir($dir, $absolute = false){
        if(!$absolute){
            $dir = $this->getParameter('kernel.root_dir').'/'.$dir;
        }
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);;
        }
        return $dir;
    }

    public function touchWeb($file){
        return $this->touch(realpath($this->getParameter('kernel.root_dir').'/../web/'.$file), true);
    }

    public function touch($file, $absolute = false){
        if(!$absolute){
            $file = $this->getParameter('kernel.root_dir').'/files/'.$file;
        }
        $this->mkdir(dirname($file), true);
        touch($file);
        return $file;
    }

    public function touchCache($file){
        return $this->touch($this->getParameter('kernel.cache_dir').'/'.$file, true);
    }

    public function getTemplates($bundle, $dir){
        $res = array();
        $files = glob($this->get('kernel')->locateResource('@'.$bundle).'/Resources/views/'.$dir.'/*.html.twig');
        foreach($files as $file){
            $res[] = $bundle.':'.$dir.':'.basename($file);
        }
        return $res;
    }

    public function getResource($res = '', $bundle = null){
        if(strpos($res, 'Bundle:')){
            $path = explode(':', $res);
            $bundle = $path[0];
            unset($path[0]);
            $res = str_replace('//', '/', 'views/'.implode('/', $path));
        }

        return $this->get('kernel')->locateResource('@'.$bundle).'Resources/'.$res;
    }


    public function bundleName($bundle = null){
        if(strpos($bundle, '\\') !== false){
            $arr = explode('\\', $bundle);
            $bundle = array_pop($arr);
        }
        return $bundle;
    }
    public function bundlePath($bundle = null){
        $bundle = $this->bundleName($bundle);
        $path = $this->get('kernel')->locateResource('@'.$bundle);
        $path = substr($path, 0, strlen($path)-1); //slash
        return $path;
    }

    public function parseYamlResource($res, $bundle = null){
        $file = $this->getResource($res, $bundle);
        if(file_exists($file)){
            $parser = new \Symfony\Component\Yaml\Parser();
            $content = file_get_contents($file);
            $content = preg_replace_callback(
                '/%(.+)%/',
                function ($matches) {
                    return $this->getParameter($matches[1]);
                },
                $content
            );
            return $parser->parse($content);
        }
        return array();
    }

    public function isDebug(){
        return $this->getParameter('kernel.debug');
    }

    public function isProd(){
        return $this->getParameter('kernel.environment') == 'prod';
    }


    /**
     * @return \AmazonS3
     */
    public function getAmazonS3(){
        return $this->get('mardraze_core.aws_s3.client');
    }

    /**
     * @param string $name
     * @return \Gaufrette\Filesystem
     */
    public function getFilesystem($name = null){
        if(!$name){
            $name = $this->getParameter('mardraze_core.default_filesystem');
        }
        return $this->container->get('knp_gaufrette.filesystem_map')->get($name);
    }

    public function getAllRoutes(){
        $router = $this->container->get('router');
        $collection = $router->getRouteCollection();
        $allRoutes = $collection->all();
        return array_keys($allRoutes);
    }

    /**
     * @return \Mardraze\CoreBundle\Service\GoogleCalendarApi
     */
    public function getGoogleCalendarApi(){
        return $this->get('mardraze_core.google_calendar_api');
    }

    /**
     * @return \Mardraze\CoreBundle\Service\GoogleDriveApi
     */
    public function getGoogleDriveApi(){
        return $this->get('mardraze_core.google_drive_api');
    }

    public function getProcessData($cmd){
        $shortCmd = substr($cmd, 1);
        $lines = explode("\n", shell_exec('ps aux | grep "'.$shortCmd.'"'));
        $processes = array();
        $hoursOld = 1;
        foreach($lines as $line){
            if(strpos($line, $cmd) !== false){
                $process = explode(' ', preg_replace('/\s+/', ' ', $line));
                $pid = $process[1];
                $process['pid'] = $pid;
                $timeCreated = $process[8];
                $lifeTime = time() - strtotime($timeCreated);
                $process['is_old'] = $lifeTime < 0 || $lifeTime > 3600 * $hoursOld;
                $processes[] = $process;
            }
        }
        return $processes;
    }


    public function killOldProcess($cmd){
        $processes = $this->getProcessData($cmd);
        foreach($processes as $process) {
            if ($process['is_old']) {
                shell_exec('kill '.$process['pid'].' &> /dev/null');
            }
        }
    }

    public function runCommand($command, $newProc = false){
        if($newProc){
            return $this->runSh($this->getParameter('kernel.root_dir').'/console '.$command);
        }
        return shell_exec($this->getParameter('kernel.root_dir').'/console '.$command);
    }

    public function runSh($cmd){
        $shFile = $cmd;
        $file = $this->touch($this->getParameter('kernel.cache_dir').'/runSh/'.date('Y-m-d').'_'.uniqid().'.sh');
        file_put_contents($file, "#!/bin/bash\n");
        file_put_contents($file, $shFile."\n", FILE_APPEND);
        $shFile = $file;
        $this->killOldProcess($shFile);
        $logFile = $this->touch('logs/runSh/'.basename($shFile).'.log');
        chmod($shFile, 0755);
        $cmd = sprintf("%s >> %s 2>&1 & echo $! > %s", $shFile, $logFile, '/dev/null');

        exec($cmd);
        return $file;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter(){
        return $this->container->get('router');
    }

    /**
     * @return \Mardraze\CoreBundle\Service\HtmlParser
     */
    public function getHtmlParser(){
        return $this->container->get('mardraze_core.html_parser');
    }

    public function httpAuth($login, $password){
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header("WWW-Authenticate: Basic realm=\"Private Area\"");
            header("HTTP/1.0 401 Unauthorized");
            print "Sorry - you need valid credentials to be granted access!\n";
            exit;
        } else {
            if (($_SERVER['PHP_AUTH_USER'] == $login) && ($_SERVER['PHP_AUTH_PW'] == $password)) {
            } else {
                header("WWW-Authenticate: Basic realm=\"Private Area\"");
                header("HTTP/1.0 401 Unauthorized");
                print "Sorry - you need valid credentials to be granted access!\n";
                exit;
            }
        }
    }

    public function makeAmazonUrl($path){
        $bucketName = $this->getParameter('amazon_s3.bucket_name');
        $amazonDirectory = $this->getParameter('mardraze_core.bundle_name');
        $amazonRegion = $this->getParameter('amazon_s3.region');
        $url = 'https://s3.'.$amazonRegion.'.amazonaws.com/'.$bucketName.'/'.$path;
        return $url;
    }


    public function setupRouter(){
        $context = $this->get('router')->getContext();
        $mainHost = $this->getCloudManager()->getMainHost();
        $host = preg_replace('/http(s)?:\/\//', '', $mainHost);
        $scheme = strpos($mainHost, 'https:') === false ? 'http' : 'https';
        $context->setHost($host);
        $context->setScheme($scheme);
    }

    /**
     * @return \Mardraze\CoreBundle\Service\Fakturownia
     */
    public function getFakturownia(){
        return $this->get('mardraze_core.fakturownia');
    }

    /**
     * @return \Mardraze\CoreBundle\Service\Ifirma
     */
    public function getIfirma(){
        return $this->get('mardraze_core.ifirma');
    }

    /**
     * @param $name
     * @param $email
     * @param $password
     * @param array $roles
     * @return \Mardraze\CoreBundle\Entity\User
     */
    public function createUser($name, $email, $password, $roles = array()){
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setUsername($name);
        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setEnabled(true);
        $user->setRoles($roles);
        $userManager->updateUser($user, true);
        return $user;
    }

    public function isMethodPost(){
        return $this->getRequest()->isMethod('POST');
    }

    /**
     * @param $user \Mardraze\CoreBundle\Entity\User
     * @param $newPassword
     */
    public function changeUserPassword($user, $newPassword){
        $user->setPlainPassword($newPassword);
        $this->get('fos_user.user_manager')->updateUser($user, true);
    }

    /**
     * @return \Mardraze\CoreBundle\Service\Sms\Sms
     */
    public function getSms() {
        return $this->get('mardraze_core.sms');
    }

    public function sendSms($phones, $templateStr, $parameters = array()) {
        $template = $this->getTwig()->loadTemplate($templateStr);
        $message_sms = $template->renderBlock('message_sms', $parameters);
        $sms = $this->get('mardraze_core.sms');
        if($sms instanceof \Mardraze\CoreBundle\Service\Sms\Sms){
            return $sms->send($phones, $message_sms);
        }
    }

    /**
     * @deprecated Use ShowCallback
     * @param $object
     * @param $actions
     * @return bool
     */
    public function canShow($object, $actions){
        if($object && method_exists($object, 'adminCanShowListAction')){
            return $object->adminCanShowListAction($this, $actions);
        }
        return true;
    }

    public function fileExt($file){
        $basenameFile = basename($file);
        $ext = substr($basenameFile, strrpos($basenameFile, '.') + 1);
        return $ext;
    }
    public function twigToPdf($srcTemplate, $destPDF, $params = array(), $styles = array(), $useTcpdf = true){
        $html = $this->getTwig()->render($srcTemplate, $params);
        if($useTcpdf){
            return $this->htmlToPdfTCPDF($html, $destPDF, array(), false);
        }
        return $this->htmlToPdf($html, $destPDF, $styles, false);
    }

    public function htmlToPdfTCPDF($srcHTML, $destPDF = null, $styles = array(), $fromFile = true){
        $html = '';
        if($fromFile){
            if(!$destPDF){
                $destPDF = $srcHTML.'.pdf';
            }
            $html = file_get_contents($srcHTML);
        }else{
            $html = $srcHTML;
        }
        foreach($styles as $style){
            $html .= str_replace('<head>', '<head>'.'<style>'.file_get_contents($style).'</style>', $html);
        }
        return $this->getPdfMaker()->makePdf(array(
            'tcpdf' => array(
                'pages' => array($html),
                'output' => array(
                    'path' => $destPDF
                )
            )
        ));
    }

    public function htmlToPdf($srcHTML, $destPDF = null, $styles = array(), $fromFile = true, $mpdfParams = array()){
        $html = '';
        if($fromFile){
            if(!$destPDF){
                $destPDF = $srcHTML.'.pdf';
            }
            $html = file_get_contents($srcHTML);
        }else{
            $html = $srcHTML;
        }
        $p = array_replace(array(
            '','A4','','',10,10,10,10,10,10
        ), $mpdfParams);

        $mpdf = new \mPDF($p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9]);
        $mpdf->useOnlyCoreFonts = true;
        $mpdf->SetProtection(array('print'));
        $mpdf->SetDisplayMode('fullpage');
        foreach($styles as $style){
            $mpdf->WriteHTML(file_get_contents($style), 1);
        }
        $mpdf->WriteHTML($html, 2);
        $mpdf->Output($destPDF,'F');
        return $destPDF;
    }

    /**
     * @return \Mardraze\InvoiceBundle\Service\MardrazeInvoice
     */
    public function getInvoiceMardraze(){
        return $this->get('mardraze_invoice.invoice');
    }

    public function createGUID() {
        $data = json_encode($_SERVER);
        $hash = strtoupper(hash('ripemd128', uniqid("", true) . md5($data)));
        $guid =
            substr($hash,  0,  8) .
            '-' .
            substr($hash,  8,  4) .
            '-' .
            substr($hash, 12,  4) .
            '-' .
            substr($hash, 16,  4) .
            '-' .
            substr($hash, 20, 12)
        ;
        return $guid;
    }

    public function arrayToXml($data, $root = 'root'){
        $xmlEncoder = new \Symfony\Component\Serializer\Encoder\XmlEncoder($root);
        $encoders = array($xmlEncoder);
        $serializer = new \Symfony\Component\Serializer\Serializer(array(), $encoders);
        return $serializer->serialize($data, 'xml');
    }

    public function authUser($user){
        if($user){
            if($user > 0){
                $user = $this->getRepository('MardrazeCoreBundle:User')->find($user);
            }
            $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                $user,
                null,
                'main',
                $user->getRoles());
            $this->get('security.context')->setToken($token);
        }else{
            $token = new \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken('', new User());

            $this->get('security.context')->setToken($token);
        }
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function makeQueryBuilder(){
        return new \Doctrine\ORM\QueryBuilder($this->getManager());
    }

    public function delete($repository, $where, $raw = false){
        if(!is_array($where) && $where > 0){
            $where = array('id' => $where);
        }
        $tableName = $this->getManager()->getClassMetadata($repository)->getTableName();
        if($raw){
            return $this->getConnection()->exec('DELETE FROM `'.$tableName.'` WHERE '.implode(' AND ', $where));
        }
        return $this->getConnection()->delete($tableName, $where);
    }

    /**
     * @return \Mardraze\InvoiceBundle\Service\PdfMaker
     */
    public function getPdfMaker(){
        return $this->get("mardraze_invoice.pdf_maker");
    }

    /**
     * @return \Mardraze\CoreBundle\Service\NipSearch
     */
    public function getNipSearch(){
        return $this->get("mardraze_core.nip_search");
    }

    public function callTwigFunction($func, $params){
        $url = $this->getTwig()->getFunction($func);
        if($url){
            return call_user_func_array($url->getCallable(), $params);
        }
    }

    /**
     * @return \Mardraze\OnePageBundle\Service\MenuMaker
     */
    public function getMenuMaker(){
        return $this->get("mardraze_onepage.menu_maker");
    }

}