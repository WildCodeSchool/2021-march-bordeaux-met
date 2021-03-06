<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/10/17
 * Time: 15:38
 * PHP version 7
 */

namespace App\Controller;

use App\Model\LogManager;
use App\Service\LogRecorder;
use App\Service\PublicLogRecorder;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

abstract class AbstractController
{
    /**
     * @var Environment
     */
    protected Environment $twig;
    protected LogRecorder $logRecorder;
    protected PublicLogRecorder $PublicLogRecorder;

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        $loader = new FilesystemLoader(APP_VIEW_PATH);
        $this->twig = new Environment(
            $loader,
            [
                'cache' => !APP_DEV, // @phpstan-ignore-line
                'debug' => APP_DEV,
            ]
        );
        $this->twig->addExtension(new DebugExtension());
        $this->twig->addGlobal('session', $_SESSION);
        $this->logRecorder = new LogRecorder();
        $this->PublicLogRecorder = new PublicLogRecorder();
        $logManager = new LogManager();
        $commentsData = $logManager->getLast5PublicLogs();
        $comments = [];
        foreach ($commentsData as $commentsDatum) {
            $comments[] = $commentsDatum['pseudo'] . ' ' . $commentsDatum['associated_text'];
        }
        $this->twig->addGlobal('comments', $comments);
    }
}
