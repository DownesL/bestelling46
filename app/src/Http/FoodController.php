<?php

namespace Http;

use Services\DatabaseConnector;
use SplFileInfo;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class FoodController
{
    public function __construct()
    {
        session_start();
        // initiate DB connection
        $this->db = DatabaseConnector::getConnection();

        // bootstrap Twig
        $loader = new FilesystemLoader(__DIR__ . '/../../resources/templates');
        $this->twig = new Environment($loader);
        $function = new TwigFunction('url', function ($path) {
            return BASE_PATH . $path;
        });
        $this->twig->addFunction($function);
    }

    public function show()
    {
        $votes = $_SESSION['votes'] ?? [];
        $res = $this->db
            ->fetchAllAssociative('SELECT * FROM foodOptions ORDER BY score DESC');
        foreach ($res as &$r) {
            $r['vote'] = $votes[$r['id']] ?? '';
        }
        echo $this->twig->render('layout.twig', [
            'choices' => $res,
            'votes' => $votes
        ]);
    }

    public function showAdd()
    {
        $values = $_SESSION['flash']['values'] ?? [];
        $errors = $_SESSION['flash']['errors'] ?? [];
        unset($_SESSION['flash']);

        echo $this->twig->render('addRestaurant.twig', ['values' => $values, 'errors' => $errors]);
    }

    public function add()
    {
        $name = $_POST['name'] ?? '';
        $price_range = $_POST['price_range'] ?? 0.0;
        $min_delivery_price = $_POST['min_delivery_price'] ?? 0.0;
        $delivery_price = $_POST['delivery_price'] ?? 0.0;
        $url = $_POST['url'] ?? '';
        $ext = '';


        $errors = [];
        if (isset($_POST['moduleAction']) && $_POST['moduleAction'] === 'add') {
            if ($name === "") {
                $errors[] = 'Voeg een geldige naam toe.';
            }
            if ($url === "") {
                $errors[] = 'Voeg een geldige url toe.';
            }
            if ($price_range < 0 || $price_range > 100) {
                $errors[] = 'Voeg een geldige prijs in.';
            } else {
                $price_range = ($price_range < 15 ? 1 : ($price_range < 25 ? 2 : 3));
            }
            if (!$min_delivery_price || $min_delivery_price < 0) {
                $errors[] = 'Voeg een geldige minimale prijs in.';
            }
            if (!$delivery_price || $delivery_price < 0) {
                $errors[] = 'Voeg een geldige leverprijs in.';
            }
            if (isset($_FILES['coverphoto']) && ($_FILES['coverphoto']['error'] === UPLOAD_ERR_OK)) {
                $ext = (new SplFileInfo($_FILES['coverphoto']['name']))->getExtension();
                if (!in_array($ext, ['jpg', 'png', 'gif', 'jpeg'])) {
                    $errors[] = 'Invalid extension. Only .jpeg, .jpg, .png or .gif allowed';
                }
            }

            if (!$errors) {
                $this->db
                    ->prepare('INSERT INTO foodOptions (name, price_range, delivery_price, min_delivery_price, ext, url) VALUES (?, ?, ?, ?, ?, ?)')
                    ->executeQuery([$name, $price_range, $delivery_price, $min_delivery_price, $ext, $url]);
                $res = $this->db
                    ->fetchAssociative('SELECT id FROM foodOptions ORDER BY id DESC LIMIT 1');
                $moved = @move_uploaded_file($_FILES['coverphoto']['tmp_name'], __DIR__ . '/../../public/img/foodOptions/' . $res['id'] . "." . $ext);
                header('location:/');
                exit();
            }
        }
        $_SESSION['flash']['values'] = [
            '$name' => $name,
            '$price_range' => $price_range,
            '$delivery_price' => $delivery_price,
            '$min_delivery_price' => $min_delivery_price,
        ];
        $_SESSION['flash']['errors'] = $errors;
        header('location: /add');
        exit();
    }

    public function vote($id)
    {
        $vote = $_POST['vote'] ?? '';
        if ($vote) {
            $restaurant = $this->db
                ->fetchAssociative('SELECT score FROM foodOptions WHERE id=?', [$id]);
            if ($restaurant) {
                $existingVote = $_SESSION['votes'][$id] ?? '';
                if ($existingVote) {
                    if ($existingVote == $vote) {
                        if ($vote !== 'upvote') {
                            $this->db->executeStatement('UPDATE foodOptions SET score = score + 1 WHERE id=?', [$id]);
                        } else {
                            $this->db->executeStatement('UPDATE foodOptions SET score = score - 1 WHERE id=?', [$id]);
                        }
                        unset($_SESSION['votes'][$id]);
                    } else {
                        if ($vote === 'upvote') {
                            $this->db->executeStatement('UPDATE foodOptions SET score = score + 2 WHERE id=?', [$id]);
                        } else {
                            $this->db->executeStatement('UPDATE foodOptions SET score = score - 2 WHERE id=?', [$id]);
                        }
                        $_SESSION['votes'][$id] = $vote;
                    }
                } else {
                    if ($vote === 'upvote') {
                        $this->db->executeStatement('UPDATE foodOptions SET score = score + 1 WHERE id=?', [$id]);
                    } else {
                        $this->db->executeStatement('UPDATE foodOptions SET score = score - 1 WHERE id=?', [$id]);
                    }
                    $_SESSION['votes'][$id] = $vote;
                }

            }
        }
        header('location:/');
        exit();
    }
    public function reset() {
        echo $this->db->executeStatement('DELETE FROM foodOptions');
    }
}