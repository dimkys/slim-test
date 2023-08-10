<?php

namespace App\Application\Controllers;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController {
    private $container;
    private $twig;
    private $db;

    // constructor receives container instance
    public function __construct(ContainerInterface $container, \Twig\Environment $twig, PDO $db) {
        $this->container = $container;
        $this->twig = $twig;
        $this->db = $db;
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $session = key_exists('session', $_COOKIE) ? $_COOKIE['session'] : null;

        if (!$session) {
            return $response->withStatus(302)->withHeader('Location', '/auth');
        }
        $query = $this->db->query("select * from user where session='{$session}';");
        $query->execute();
        if ($result = $query->fetch()) {
            $response->getBody()->write($this->twig->render('home.twig', ['login' => $result['login']]));
        }
        return $response;
    }

    public function getRegister(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $response->getBody()->write($this->twig->render('auth/register.twig', ['name' => '[' . 1 . ']']));
        return $response;
    }

    public function postRegister(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $params = $request->getParsedBody();
        if ($params['login'] && $params['password']) {

            if (strlen($params['login']) < 2 or strlen($params['login']) > 20) {
                $response->getBody()->write($this->twig->render('auth/register.twig', ['error' => 'Login: must be between 2 and 20 symbol']));
                return $response;
            }

            if (!preg_match('/^[a-zA-Z0-9]*$/', $params['login'])) {
                $response->getBody()->write($this->twig->render('auth/register.twig', ['error' => 'Login: must be letter or digit']));
                return $response;
            }

            if (strlen($params['password']) < 5) {
                $response->getBody()->write($this->twig->render('auth/register.twig', ['error' => 'Password: length must be more at 5']));
                return $response;
            }

            if (!preg_match('/^\D*$/', $params['password'])) {
                $response->getBody()->write($this->twig->render('auth/register.twig', ['error' => 'Password: Digit it is feee(']));
                return $response;
            }

            $hash = hash('md5', $params['login'] . time());

            $userExist = $this->db->query("select * from user where login = '{$params['login']}' LIMIT 1;");

            if ($userExist) {
                $response->getBody()->write($this->twig->render('auth/register.twig', ['error' => 'User has exist']));
                return $response;
            }

            $query = $this->db->query("INSERT INTO user (login, password, session) VALUES ('{$params['login']}', '{$params['password']}', '{$hash}');");
            if ($query) {
                return $response->withStatus(302)->withHeader('Location', '/');
            }
        }

        $response->getBody()->write($this->twig->render('auth/register.twig', ['error' => 'Всё плохо']));
        return $response;
    }


    public function auth(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $query = $this->db->query("select * from user LIMIT 1;");
        $query->execute();
        $result = $query->fetch();
        $response->getBody()->write($this->twig->render('auth/login.twig', (array)$result ?? []));
        return $response;
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        setcookie("session", "", time() - 3600);
        return $response->withStatus(302)->withHeader('Location', '/auth');
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $params = $request->getParsedBody();
        if ($params['login'] && $params['password']) {

            $query = $this->db->prepare('select * from user where login = :login and password = :password LIMIT 1;');
            $query->bindParam(':login', $params['login']);
            $query->bindParam(':password', $params['password']);
            $query->execute();
            $result = $query->fetch();

            if ($result) {
                $hash = hash('md5', $params['login'] . time());
                $query = $this->db->prepare('update user set session=:session where login = :login;');
                $query->execute([
                    'session'=>$hash,
                    'login'=>$params['login']
                ]);
                $query->fetch();
                $response = $response->withStatus(302)->withHeader('Location', '/');
                setcookie('session', $hash, 0, "/");
                return $response;
            } else {
                $response->getBody()->write($this->twig->render('auth/login.twig', ['error' => $result]));
                return $response;
            }
        }

        $response->getBody()->write($this->twig->render('auth/login.twig', ['error' => 'Всё плохо']));
        return $response;
    }
}
