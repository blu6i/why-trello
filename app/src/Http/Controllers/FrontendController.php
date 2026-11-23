<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\LogoutRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Response;
use App\Http\Responses\ResponseModelInterface;
use App\Models\Board;
use App\Security\Csrf;
use App\Security\UserSessionAuth;
use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;

/**
 * Фронтенд-контроллер приложения (FrontendController).
 *
 * Обрабатывает пользовательские действия:
 * - Регистрацию, авторизацию и разлогин пользователя через специализированные классы запросов.
 * - Заготовки действий для досок и колонок в соответствии со спецификацией проекта.
 */
class FrontendController extends AbstractController
{
    /** @var UserSessionAuth Сервис авторизации пользователя из сессии */
    protected UserSessionAuth $userSessionAuth;

    /**
     * Конструктор контроллера с инициализацией сервиса авторизации.
     *
     * @param  UserSessionAuth|null  $userSessionAuth  Сервис аутентификации.
     */
    public function __construct(?UserSessionAuth $userSessionAuth = null)
    {
        $this->userSessionAuth = $userSessionAuth ?? new UserSessionAuth;
    }

    /**
     * Главная страница интерфейса приложения.
     *
     * @param  Request  $request  Входящий HTTP-запрос.
     * @return Response HTTP-ответ с отрендеренным шаблоном home.
     */
    public function indexAction(Request $request): Response
    {
        $currentUser = $this->userSessionAuth->authorizeFromSession();

        $dbVersion = null;
        $dbError = null;

        try {
            $dbVersion = (string) Capsule::connection()->selectOne('SELECT version()')->version;
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }

        $flashSuccess = $this->getFlash('success');
        $flashError = $this->getFlash('error');

        if ($request->query('logged_out') === '1') {
            $flashSuccess = 'Вы успешно вышли из системы. Сессионная кука удалена.';
        }

        return $this->render('home', [
            'currentUser' => $currentUser,
            'csrfToken' => Csrf::token(),
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'phpVersion' => PHP_VERSION,
            'hasPdoPgsql' => extension_loaded('pdo_pgsql'),
            'hostname' => gethostname(),
            'dbVersion' => $dbVersion,
            'dbError' => $dbError,
        ]);
    }

    /**
     * Действие регистрации нового пользователя.
     *
     * @param  Request  $request  Входящий HTTP-запрос с параметрами [name, email, password].
     * @return Response HTTP-ответ (JSON или редирект с Flash-сообщением).
     */
    public function registerAction(Request $request): Response
    {
        $registerRequest = new RegisterRequest($request, $this->userSessionAuth->authorizeFromSession());
        $registerRequest->setAuth($this->userSessionAuth);

        $responseModel = $registerRequest->handle();

        if ($responseModel->isSuccess()) {
            Csrf::rotate();
        }

        if ($request->isJson()) {
            return $this->apiResponseFromModel($responseModel);
        }

        if ($responseModel->isSuccess()) {
            $this->flash('success', $responseModel->getMessage());
        } else {
            $this->flash('error', $responseModel->getMessage());
        }

        return $this->redirect('/', 303);
    }

    /**
     * Действие авторизации (входа в систему) пользователя.
     *
     * @param  Request  $request  Входящий HTTP-запрос с учетными данными [email, password].
     * @return Response HTTP-ответ (JSON или редирект с Flash-сообщением).
     */
    public function loginAction(Request $request): Response
    {
        $loginRequest = new LoginRequest($request, $this->userSessionAuth->authorizeFromSession());
        $loginRequest->setAuth($this->userSessionAuth);

        $responseModel = $loginRequest->handle();

        if ($responseModel->isSuccess()) {
            Csrf::rotate();
        }

        if ($request->isJson()) {
            return $this->apiResponseFromModel($responseModel);
        }

        if ($responseModel->isSuccess()) {
            $this->flash('success', $responseModel->getMessage());
        } else {
            $this->flash('error', $responseModel->getMessage());
        }

        return $this->redirect('/', 303);
    }

    /**
     * Действие разлогина (выхода) пользователя из системы.
     *
     * @param  Request  $request  Входящий HTTP-запрос.
     * @return Response HTTP-ответ (JSON или редирект с флагом logged_out).
     */
    public function logoutAction(Request $request): Response
    {
        $logoutRequest = new LogoutRequest($request, $this->userSessionAuth->authorizeFromSession());
        $logoutRequest->setAuth($this->userSessionAuth);

        $responseModel = $logoutRequest->handle();

        if ($request->isJson()) {
            return $this->apiResponseFromModel($responseModel);
        }

        return $this->redirect('/?logged_out=1', 303);
    }

    /**
     * Получение текущего авторизованного пользователя из сессии (для React SPA).
     *
     * @param  Request  $request  Входящий HTTP-запрос.
     * @return Response JSON-конверт с данными пользователя или ошибкой 400 для гостя.
     */
    public function userAction(Request $request): Response
    {
        $user = $this->userSessionAuth->authorizeFromSession();

        if ($user === null) {
            return $this->json([
                'is_successful' => false,
                'time' => date(DATE_ATOM),
                'error' => 'Unauthorized',
            ], 400);
        }

        return $this->json([
            'is_successful' => true,
            'time' => date(DATE_ATOM),
            'user_id' => $user->user_id,
            'email' => $user->email,
            'name' => $user->name,
            'created_datetime' => (string) $user->created_datetime,
            'updated_datetime' => $user->updated_datetime !== null ? (string) $user->updated_datetime : null,
        ]);
    }

    /**
     * Преобразование ResponseModelInterface в формат JSON-конверта (is_successful / time) для SPA.
     *
     * @param  ResponseModelInterface  $model  Модель ответа после выполнения запроса.
     * @return Response HTTP JSON-ответ.
     */
    private function apiResponseFromModel(ResponseModelInterface $model): Response
    {
        if ($model->isSuccess()) {
            $data = is_array($model->getData()) ? $model->getData() : [];
            $user = $this->userSessionAuth->authorizeFromSession();

            if ($user !== null && isset($data['user_id'])) {
                $data['created_datetime'] = (string) $user->created_datetime;
                $data['updated_datetime'] = $user->updated_datetime !== null ? (string) $user->updated_datetime : null;
            }

            return $this->json(
                array_merge([
                    'is_successful' => true,
                    'time' => date(DATE_ATOM),
                ], $data),
                $model->getStatusCode()
            );
        }

        $errors = $model->getErrors();
        $logicError = is_string($errors['logic'] ?? null) ? $errors['logic'] : $model->getMessage();

        $errorCode = match ($logicError) {
            'Пользователь с таким Email уже зарегистрирован.' => 'User already exists',
            'Неверный Email или пароль.' => 'User authorization error',
            default => $logicError,
        };

        $payload = [
            'is_successful' => false,
            'time' => date(DATE_ATOM),
            'error' => $errorCode,
        ];

        if (isset($errors['parameters']) && is_array($errors['parameters']) && count($errors['parameters']) > 0) {
            $payload['parameter_errors'] = $errors['parameters'];
        }

        return $this->json($payload, $model->getStatusCode());
    }

    // =========================================================================
    // Заготовки методов со скриншота (Board, Board column)
    // =========================================================================

    /**
     * Получение списка доступных досок пользователя.
     * Спецификация: Get, Authorized.
     *
     * @param  Request  $request  Входящий HTTP-запрос.
     * @return Response Список досок в формате JSON или представление.
     */
    public function boardListAction(Request $request): Response
    {
        $user = $this->userSessionAuth->authorizeFromSession();
        if ($user === null) {
            return $this->redirect('/login');
        }

        $boards = Board::where('owner_user_id', $user->user_id)->get();

        return $this->json([
            'success' => true,
            'boards' => $boards,
        ]);
    }

    /**
     * Создание новой доски.
     * Спецификация: Post, Authorized.
     *
     * @param  Request  $request  Входящий HTTP-запрос с параметрами доски [title, description].
     * @return Response Результат создания доски.
     */
    public function createBoardAction(Request $request): Response
    {
        $user = $this->userSessionAuth->authorizeFromSession();
        if ($user === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->json(['error' => 'Название доски обязательно'], 422);
        }

        $board = Board::create([
            'owner_user_id' => $user->user_id,
            'title' => $title,
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Доска создана успешно',
            'board' => $board,
        ], 201);
    }

    /**
     * Редактирование существующей доски.
     * Спецификация: Post, Authorized.
     *
     * @param  Request  $request  Входящий HTTP-запрос с [board_id, title].
     * @return Response Результат обновления.
     */
    public function editBoardAction(Request $request): Response
    {
        $user = $this->userSessionAuth->authorizeFromSession();
        if ($user === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $boardId = (int) $request->input('board_id', 0);
        $board = Board::where('board_id', $boardId)
            ->where('owner_user_id', $user->user_id)
            ->first();

        if ($board === null) {
            return $this->json(['error' => 'Доска не найдена'], 404);
        }

        $title = trim((string) $request->input('title', ''));
        if ($title !== '') {
            $board->title = $title;
            $board->save();
        }

        return $this->json([
            'success' => true,
            'message' => 'Доска обновлена',
            'board' => $board,
        ]);
    }

    /**
     * Удаление доски.
     * Спецификация: DELETE, Authorized.
     *
     * @param  Request  $request  Входящий HTTP-запрос с параметром [board_id].
     * @return Response Результат удаления.
     */
    public function deleteBoardAction(Request $request): Response
    {
        $user = $this->userSessionAuth->authorizeFromSession();
        if ($user === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $boardId = (int) ($request->param('board_id') ?? $request->input('board_id', 0));
        $board = Board::where('board_id', $boardId)
            ->where('owner_user_id', $user->user_id)
            ->first();

        if ($board === null) {
            return $this->json(['error' => 'Доска не найдена'], 404);
        }

        $board->delete();

        return $this->json([
            'success' => true,
            'message' => 'Доска успешно удалена',
        ]);
    }

    /**
     * Получение колонок доски.
     * Спецификация: Get, Authorized, board_id.
     *
     * @param  Request  $request  Входящий HTTP-запрос с [board_id].
     * @return Response Список колонок выбранной доски.
     */
    public function boardColumnsAction(Request $request): Response
    {
        $user = $this->userSessionAuth->authorizeFromSession();
        if ($user === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $boardId = (int) ($request->query('board_id') ?? $request->param('board_id', 0));
        $board = Board::where('board_id', $boardId)
            ->where('owner_user_id', $user->user_id)
            ->first();

        if ($board === null) {
            return $this->json(['error' => 'Доска не найдена'], 404);
        }

        $columns = $board->columns()->orderBy('ordinal_position', 'asc')->get();

        return $this->json([
            'success' => true,
            'board_id' => $boardId,
            'columns' => $columns,
        ]);
    }
}
