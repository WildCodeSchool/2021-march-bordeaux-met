<?php

namespace App\Service;

use App\Model\ScoreManager;
use App\Model\UserManager;

class GameChecker
{
    public function checkStatus()
    {
        $highestScoreRecorded = 0;
        if ($_SESSION['game']['status'] === 'Game Over') {
            $scoreManager = new ScoreManager();
            if ($scoreManager->getScoresByDepartment($_SESSION['deptId'])) {
                $highestScoreRecorded = (int)$scoreManager->getScoresByDepartment($_SESSION['deptId'])[0]['best_score'];
            }

            $userManager = new UserManager();
            $userManager->addOneGameToTotalGames($_SESSION['id']);

            $scoreManager = new ScoreManager();
            $scores = $scoreManager->checkScoreAlreadyExists($_SESSION['id'], $_SESSION['deptId']);
            if (empty($scores)) {
                $scoreManager = new ScoreManager();
                $scoreManager->insertNewBestScoreOnDept(
                    $_SESSION['id'],
                    $_SESSION['deptId'],
                    $_SESSION['game']['currentScore']
                );
            } elseif ($_SESSION['game']['currentScore'] > $scores[0]['best_score']) {
                $scoreManager = new ScoreManager();
                $scoreManager->updateBestScoreByUserDept(
                    $_SESSION['id'],
                    $_SESSION['deptId'],
                    $_SESSION['game']['currentScore']
                );
            }

            if ($_SESSION['game']['currentScore'] > $highestScoreRecorded) {
                $publicLogRecorder = new PublicLogRecorder();
                $publicLogRecorder->recordNewFirst();
            }
        }
        return $highestScoreRecorded;
    }
}
