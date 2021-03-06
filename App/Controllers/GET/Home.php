<?php

declare(strict_types = 1);

namespace App\Controllers\GET;

use Core\Utilities;
use Core\View;
use Core\Controller;

use App\Models\User;
use App\Models\Quiz;

class Home extends Controller {
	/**
	 * Show the index page
	 *
	 * @return void
	 */
	public function runAction() {
		$user = new User();

		if(!$user->isLoggedIn()){
			header("Location: /login");
			return;
		}

		$quiz = new Quiz();

		View::renderTemplate('Home/index.twig', [
			'quizzes' => $quiz->getAllQuizzes()
		]);
	}
}
