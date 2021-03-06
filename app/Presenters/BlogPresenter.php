<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\UserModel;
use Nette\Utils\ArrayHash;
use App\Forms\BlogFactory;
use app\Models\BlogModel;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\HiddenField;
use Nette\Application\BadRequestException;

/**
 * Blog Presenter
 * @package App\Presenters
 */
final class BlogPresenter extends Presenter
{
    const
    FORM_MSG_REQUIRED = 'This field is required';

    public UserModel $user;

    public string $status = '';
    //Home page Blog => last added -> by id?
    private $defaultBlogId;
    public $result = '';

    /** @var BlogModel Blog Model. */
    private $blogModel;

    //for edit/delete
    public string $blog = '';
    public int $blog_id = 0;
    public string $title = '';
    public string $description = '';
    public string $content = '';
    public int $user_id = 0;
    
    public array $blogs = [];
    public BlogFactory $forms;

     /**
     * Construct with default Blog id
     * @param int $defaultBlogId Blog ID
     * @param BlogModel $BlogModel Blog Model
     */
    public function __construct(
        string $defaultBlogId = null,
        BlogModel $blogModel,
        UserModel $user,
        BlogFactory $forms
    ) {
        parent::__construct();
        $this->defaultBlogId = $defaultBlogId;
        $this->blogModel = $blogModel;
        $this->user = $user;
        $this->forms = $forms;

        isset($_SESSION['user_id'])? $this->user_id = $_SESSION['user_id'] : 0;
    }

    public function checkAuth()
    {
        //check if user loged
        if ($this->user_id == 0) {
            $this->flashMessage('Sorry, it look like you are not loged in.', 'alert');
            $this->redirect('Login:default');
        }
    }

    /**
     * Read the Default Blog template.
     * @param string|null $id Blog id
     * @throws BadRequestException if not found
     */
    public function renderDefault()
    {
        $blog = $this->blogModel->getBlogs();

        // Read the Blog -> 404 if not found.
        if ($blog == 'No blog') {
            $this->flashMessage('There are not any blogs in here yet.', 'fail');
        }

        $this->template->blog = $blog; // Send to template.
    }

    public function handleDelete($blog_id, $user_id)
    {
        $result = $this->blogModel->removeBlog($blog_id, $user_id);

        if ($result == "success") {
            $this->status = "success";
            $this->flashMessage('Blog has been deleted.', 'success');
        } else {
            //redirect
            $this->status = "fail";
            $this->flashMessage('Sorry, there was a unexpected error in deleting the Blog.', 'fail');
        }
        $this->redirect('Blog:default');
    }
    
    /**
     * Add the Blog section
     */
    public function renderAdd()
    {
        //check if loged in -> if not redirect
        $this->checkAuth();
    }

    protected function createComponentBlogForm()
    {
        $form = $this->forms->renderForm("");
        $form->onSuccess[] = [$this, 'blogFormSucceeded'];
        return $form;
    }

    public function blogFormSucceeded(ArrayHash $values)
    {
        $result = $this->blogModel->saveBlog($values);

        if ($result == "success") {
            //redirect 2 userPage
            $this->flashMessage('Blog has been saved.', 'success');
            $this->redirect('Blog:default');
        } else {
            //redirect
            $this->flashMessage('Sorry, there was a unexpected error in saving the Blog.', 'fail');
            $this->redirect('Blog:add');
        }
    }

    /**
     * Edit the Blog section
     */
    public function renderEdit(array $blog)
    {
        $this->blogs = $blog;
        $this->template->blog = $this->blog; // Send to template.
    }

    protected function createComponentEditForm()
    {
        $form = $this->forms->renderForm($this->blogs);
        $form->onSuccess[] = [$this, 'editFormSucceeded'];
        return $form;
    }

    public function editFormSucceeded(ArrayHash $blog)
    {
   
        $result = $this->blogModel->updateBlog($blog);

        if ($result == "success") {
            //redirect 2 userPage
            $this->status = "success";
            $this->flashMessage('Blog has been updated.', 'success');
        } else {
            //redirect
            $this->status = "fail";
            $this->flashMessage('Sorry, there was a unexpected error in updating of the blog.', 'fail');
        }
        $this->redirect('Blog:default');
    }
}
