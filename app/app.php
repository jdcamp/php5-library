<?php
    date_default_timezone_set('America/Los_Angeles');

    require_once __DIR__."/../vendor/autoload.php";
    require_once __DIR__."/../app/AppHelperFunctions.php";
    require_once __DIR__."/../src/Author.php";
    require_once __DIR__."/../src/AuthorBook.php";
    require_once __DIR__."/../src/Book.php";
    require_once __DIR__."/../src/BookCopy.php";
    require_once __DIR__."/../src/Checkout.php";
    require_once __DIR__."/../src/Genre.php";
    require_once __DIR__."/../src/GenreBook.php";
    require_once __DIR__."/../src/Patron.php";

    $app = new Silex\Application();
    $app['debug'] = true;
    $server = 'mysql:host=localhost:8889;dbname=library';
    $username = 'root';
    $password = 'root';
    $DB = new PDO($server, $username, $password);

    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/../views'
    ));

    use Symfony\Component\HttpFoundation\Request;
    Request::enableHttpMethodParameterOverride();

    $app->get("/", function() use ($app) {
        return $app['twig']->render("index.html.twig");
    });

    $app->get("/librarian_view", function() use ($app) {
        return $app['twig']->render("librarian.html.twig", array('books' => Book::getSome('all')));
    });

    $app->post("/book_add", function() use ($app) {
        $book_title = $_POST['book_title'];
        $publish_date = $_POST['publish_date'];
        $synopsis = $_POST['synopsis'];
        $author_name = $_POST['author_name'];
        $book_copies = (int)$_POST['copies'];
        $author = AppHelperFunctions::getOrCreateAuthor($author_name);
        $author_id = $author->getId();
        $new_book = new Book($book_title, $publish_date, $synopsis);
        $new_book->save();
        $new_book_id = $new_book->getId();
        $new_author_book = new AuthorBook($author_id, $new_book_id);
        $new_author_book->save();

        for ($i = 0; $i < $book_copies; $i ++) {
            $new_book_copy = new BookCopy($new_book_id, 5, 'new');
            var_dump($new_book_copy);
            $new_book_copy->save();
        }

        return $app->redirect("/librarian_view");
    });

    $app->get("/book/{id}", function($id) use ($app){
        $copies = BookCopy::getSome('all');
        $book = Book::getSome('id', $id);
        return $app['twig']->render('edit_book.html.twig', array('book' => $book[0], 'copies' => $copies));
    });

    $app->post("/add_author", function() use ($app) {
        $new_author = $_POST['add_author'];
        $id = (int) $_POST['book_id'];
        $author = AppHelperFunctions::getOrCreateAuthor($new_author);
        $author_id = $author->getId();
        $new_author_book = new AuthorBook($author_id, $id);
        $new_author_book->save();

        return $app->redirect('/book/'.$id);
    });

    $app->patch("/update_book", function() use ($app) {
        $new_title = $_POST['update_title'];
        $new_synopsis = $_POST['update_synopsis'];
        $book_id = $_POST['book_id'];
        $new_book = Book::getSome('id', $book_id);
        $new_book[0]->updateTitle($new_title);
        $new_book[0]->updateSynopsis($new_synopsis);

        return $app->redirect('/librarian_view');
    });

    $app->get("/edit_book_copy/{id}", function($id) use ($app) {
        $copy = BookCopy::getSome('id', $id);
        return $app['twig']->render('edit_book_copy.html.twig', array('copy' => $copy[0]));
    });

    $app->patch("/update_book_copy/{id}", function($id) use ($app) {
        $book = Book::getSome('id', $id);
        $new_condition = $_POST['update_condition'];
        $new_comments = $_POST['update_comments'];
        $new_book_copy = BookCopy::getSome('id', $id);
        $book_id = $new_book_copy[0]->getBookId();
        $new_book_copy[0]->update($book_id, $new_condition, $new_comments);
        return $app->redirect('/book/'.$book_id);
    });

    return $app;
 ?>