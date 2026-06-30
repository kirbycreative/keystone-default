<?php

namespace Keystone\Toolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Keystone\Toolkit\Models\AppModel;
use Keystone\Toolkit\Support\ModelRegistry;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Generic REST endpoint for whitelisted AppModels. The juice ApiDatabase driver
 * targets these routes, and every write is validated with the model's own
 * `rules()` — the same `$properties` that build the forms — so the client and
 * server validate identically with no duplicated rule definitions.
 */
class ModelController extends Controller
{
    /**
     * @return class-string<AppModel>
     */
    protected function resolveClass(string $model): string
    {
        $class = ModelRegistry::resolve($model);

        if ($class === null) {
            abort(404, "Model [{$model}] is not registered.");
        }

        return $class;
    }

    /**
     * Authorize an ability, honouring a Gate policy when one exists. With no
     * policy, access falls back to the registry whitelist + auth unless the app
     * opts into `require_policy`.
     */
    protected function authorizeModel(string $ability, string $class, $argument): void
    {
        if (Gate::getPolicyFor($class) !== null) {
            Gate::authorize($ability, $argument);

            return;
        }

        if (config('keystone.models.require_policy', false)) {
            throw new AccessDeniedHttpException("No policy registered for [{$class}].");
        }
    }

    /**
     * Validate input against the model rules. For partial updates only the
     * supplied fields are validated, so field-level autosave never trips on
     * unrelated required columns.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function validateInput(string $class, array $input, bool $partial): array
    {
        $rules = $class::rules();

        if ($partial) {
            $rules = Arr::only($rules, array_keys($input));
        }

        if ($rules === []) {
            return Arr::only($input, (new $class)->getFillable());
        }

        return Validator::make($input, $rules)->validate();
    }

    public function create(Request $request, string $model): JsonResponse
    {
        $class = $this->resolveClass($model);
        $this->authorizeModel('create', $class, $class);

        $data = $this->validateInput($class, $request->all(), false);

        $instance = new $class;
        $instance->fill($data);
        $instance->save();

        return response()->json($instance->fresh());
    }

    public function find(string $model, $id): JsonResponse
    {
        $class = $this->resolveClass($model);
        $instance = $class::findOrFail($id);
        $this->authorizeModel('view', $class, $instance);

        return response()->json($instance);
    }

    public function update(Request $request, string $model, $id): JsonResponse
    {
        $class = $this->resolveClass($model);
        $instance = $class::findOrFail($id);
        $this->authorizeModel('update', $class, $instance);

        $data = $this->validateInput($class, $request->all(), true);

        $instance->fill($data);
        $instance->save();

        return response()->json($instance->fresh());
    }

    public function delete(string $model, $id): JsonResponse
    {
        $class = $this->resolveClass($model);
        $instance = $class::findOrFail($id);
        $this->authorizeModel('delete', $class, $instance);

        $instance->delete();

        return response()->json(['deleted' => true, 'id' => $id]);
    }

    public function query(Request $request, string $model): JsonResponse
    {
        $class = $this->resolveClass($model);
        $this->authorizeModel('viewAny', $class, $class);

        $query = $class::query();

        // Only allow filtering on real, fillable columns to avoid leaking scopes.
        $fillable = (new $class)->getFillable();
        foreach (Arr::only($request->all(), $fillable) as $column => $value) {
            $query->where($column, $value);
        }

        return response()->json($query->get());
    }
}
