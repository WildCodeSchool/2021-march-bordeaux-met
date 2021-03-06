<?php

namespace App\Controller;

use App\Model\AbstractManager;
use App\Model\AdminManager;
use App\Model\DepartmentManager;
use App\Model\GameAdminManager;
use App\Model\LogManager;
use App\Model\UserManager;
use App\Service\FormChecker;
use DateInterval;
use DateTimeImmutable;

class AdminController extends AbstractController
{

    public function deleteBadge(string $pseudo, string $id)
    {
        $adminManager = new AdminManager();
        $adminManager->deleteBadge($pseudo, $id);
        header('Location: /admin/show/' . $pseudo);
    }

    public function addBadge(string $pseudo, string $idBadge)
    {
        $adminManager = new AdminManager();
        $idUser = $adminManager->getInfosByPseudo($pseudo)['profileInfo']['id'];
        $adminManager->addBadge($idUser, $idBadge);
        header('Location: /admin/show/' . $pseudo);
    }


    public function home()
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: /');
        }
        $adminManager = new AdminManager();
        $names = $adminManager->getNames();
        $badges = $adminManager->showAllbadgesAndUsers();
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formChecker = new FormChecker($_POST);
            $formChecker->cleanAll();
            $trimmedPost = $formChecker->getPost();
            $formChecker->checkInputLength($trimmedPost['pseudo'], 'pseudo', 1, 255);
            $errors = $formChecker->getErrors();
            $search = $formChecker->getPost();
            $search['pseudo'] = ucfirst(strtolower($search['pseudo']));
            if (!in_array($search['pseudo'], $names)) {
                $errors['exist'] = "Ce nom n'existe pas dans la base donnée";
            }
            if (empty($errors)) {
                header('Location: /Admin/show/' . $search['pseudo']);
            }
        }
        return $this->twig->render(
            '/Admin/home.html.twig',
            ['errors' => $errors, 'names' => $names, 'badges' => $badges]
        );
    }

    public function show(string $pseudo)
    {
        $adminManager = new AdminManager();
        $userData = $adminManager->getInfosByPseudo($pseudo);
        return $this->twig->render('/Admin/show.html.twig', ['user_data' => $userData]);
    }

    public function isAdmin($pseudo)
    {
        $adminManager = new AdminManager();
        $adminManager->changeIsAdminSatus($pseudo);

        header('Location: /admin/show/' . $pseudo);
    }

    public function changeAvatar(string $pseudo, string $avatarId)
    {
        $adminManager = new AdminManager();
        $adminManager->changeAvatar($pseudo, $avatarId);

        if ($_SESSION['pseudo'] === $pseudo) {
            $_SESSION['avatar'] = $adminManager->getAvatarbiId($avatarId)['image'];
        }
        header('Location: /admin/show/' . $pseudo);
    }

    public function upload()
    {
        $adminManager = new AdminManager();
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $uploadDir = 'assets/images/badges/';
            $nextBadgeID = $adminManager->getNextBadgeId();
            $uploadFile = $uploadDir . 'badge' . $nextBadgeID . '.png';
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $extensionsOk = ['png'];
            $maxFileSize = 1000000;
            if ((!in_array($extension, $extensionsOk))) {
                $errors[] = 'Veuillez sélectionner une image de type png !';
            }
            if (file_exists($_FILES['image']['name']) && filesize($_FILES['image']['name']) > $maxFileSize) {
                $errors[] = "Votre fichier doit faire moins de 1M !";
            }
            if (empty($errors)) {
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile);
                $adminManager->insertBadge('badge' . $nextBadgeID . '.png');
            }
        }
        $names = $adminManager->getNames();
        $badges = $adminManager->showAllbadgesAndUsers();
        return $this->twig->render(
            '/Admin/home.html.twig',
            ['anomalies' => $errors ,'names' => $names, 'badges' => $badges]
        );
    }

    public function deleteBadgeById($badgeId)
    {
        $adminManager = new AdminManager();
        $image = $adminManager->getBadgeImagebyId($badgeId);
        if (unlink('assets/images/badges/' . $image)) {
            $adminManager->deleteBadgebyImage($image);
        };
        $names = $adminManager->getNames();
        $badges = $adminManager->showAllbadgesAndUsers();
        return $this->twig->render('/Admin/home.html.twig', ['names' => $names, 'badges' => $badges]);
    }
    public function graph()
    {
        return $this->twig->render('/Admin/graph.html.twig');
    }
    public function graphData()
    {
        $logManager = new LogManager();
        $startDate = new DateTimeImmutable($_POST['startDate']);
        $endDate = new DateTimeImmutable($_POST['endDate']);
        $realStartDate =  min($startDate, $endDate);
        $realEndDate =  max($startDate, $endDate);
        $realEndDate = $realEndDate->modify('+1 day');
        $logins = $logManager->countByLogNameAndByPeriod(
            'login',
            $realStartDate->format('Y/m/d'),
            $realEndDate->format('Y/m/d')
        );
        $games = $logManager->countByLogNameAndByPeriod(
            'End of Game',
            $realStartDate->format('Y/m/d'),
            $realEndDate->format('Y/m/d')
        );
        $newPlayers = $logManager->countByLogNameAndByPeriod(
            'New signup',
            $realStartDate->format('Y/m/d'),
            $realEndDate->format('Y/m/d')
        );
        $response = [];
        foreach ($logins as $login) {
            $response['logins'] [$login['date']] = (int)$login['total'];
        }
        foreach ($games as $game) {
            $response['games'] [$game['date']] = (int)$game['total'];
        }
        foreach ($newPlayers as $newPlayer) {
            $response['newPlayers'] [$newPlayer['date']] = (int)$newPlayer['total'];
        }
        $response['startDate'] = $realStartDate->format('d/m/Y');
        $realEndDate = $realEndDate->modify('-1 day');

        $response['endDate'] = $realEndDate->format('d/m/Y');
        return $this->twig->render('Admin/graph.html.twig', ['data_source' => json_encode($response)]);
    }

    public function tab()
    {

        $logsToFollow = [];
        $dates = [];
        $logManager = new LogManager();
        $allLogsName = $logManager->getLogsALl();
        if ($_SERVER['REQUEST_METHOD'] === "POST") {
            $startDate = new DateTimeImmutable($_POST['startDate']);
            $endDate = new DateTimeImmutable($_POST['endDate']);
            $realStartDate =  min($startDate, $endDate);
            $realEndDate =   max($startDate, $endDate);
            $realEndDate = $realEndDate->modify('+1 day');
            $_POST['startDate'] = $realStartDate->format('Y/m/d');
            $_POST['endDate'] = $realEndDate->format('Y/m/d');

            $logsToFollow = $logManager->getLogsbyLogNamesInAPeriod($_POST);
            $realEndDate = $realEndDate->modify('-1 day');

            $dates = [
                'start' => $realStartDate->format('d/m/Y'),
                'end' => $realEndDate->format('d/m/Y'),
            ];
        }
        return $this->twig->render(
            '/Admin/statTable.html.twig',
            ['allLogsName' => $allLogsName, 'logsToFollow' => $logsToFollow, 'dates' => $dates]
        );
    }
}
