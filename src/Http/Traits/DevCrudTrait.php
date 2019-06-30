<?php
/**
 * Project      : DevCrud
 * File Name    : CrudAble.php
 * User         : Abu Bakar Siddique
 * Email        : absiddique.live@gmail.com
 * Date[Y/M/D]  : 2019/06/29 6:36 PM
 */

namespace TunnelConflux\DevCrud\Http\Traits;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TunnelConflux\DevCrud\Http\Requests\SaveRequest;
use TunnelConflux\DevCrud\Http\Requests\UpdateRequest;
use TunnelConflux\DevCrud\Models\DevCrudModel;

trait DevCrudTrait
{
    public function index()
    {
        if (view()->exists("{$this->viewPrefix}.index")) {
            return view("{$this->viewPrefix}.index", (array)$this);
        }

        return view('crud.index', (array)$this);
    }

    public function create()
    {
        $this->checkNewActionStatus();

        if (view()->exists("{$this->viewPrefix}.form")) {
            return view("{$this->viewPrefix}.form", (array)$this);
        }

        return view('crud.form', (array)$this);
    }

    public function store(SaveRequest $request, DevCrudModel $devCrudModel = null)
    {
        $files = [];

        $this->checkNewActionStatus();
        $this->validate($request, $this->getValidationRules(), $this->getValidationMessages());

        foreach ($this->formItems as $key => $item) {
            if ($request->file($key)) {
                if (@$item[1] == 'image') {
                    $files[$key] = saveFile($request->file($key), $this->uploadPath);
                } elseif (@$item[1] == 'file') {
                    $files[$key] = saveFile($request->file($key), $this->uploadPath);
                } elseif (@$item[1] == 'video') {
                    $files[$key] = saveFile($request->file($key), $this->uploadPath);
                }
            }
        }

        $request->request->add($files);

        $inputs = $request->input();

        foreach ($inputs as $key => $val) {
            $inputs[$key] = $val ?: (($val === 0) ? 0 : null);
        }

        $this->data = $this->model->create($inputs);

        if ($this->data) {
            try {
                foreach ($this->formHasParents as $key => $val) {
                    $joinModel = $this->model->getFormRelationalModel($key);

                    if ($joinModel->getJoinType() == 'manyToMany') {
                        $this->data->{$key}()->sync($request->input($key));
                    } elseif ($joinModel->getJoinType() == 'oneToMany') {
                        $this->data->{$key . "_id"} = @$inputs[$key . "_id"];
                        $this->data->save();
                    }
                }
            } catch (Exception $e) {

            }
            $this->actionMessage = ['success' => 'Item Added Successfully !'];
        } else {
            $this->actionMessage = ['error' => 'Item Added Failed !'];

            foreach ($files as $file) {
                @unlink($this->uploadPath . '/' . $file);
            }
        }

        if ($this->redirectAfterAction) {
            $this->redirectToSingleView();
        }
    }

    public function show()
    {
        $this->checkActionStatus($this->indexViewAction);

        if (view()->exists("{$this->viewPrefix}.show")) {
            return view("{$this->viewPrefix}.show", (array)$this);
        }

        return view('crud.show', (array)$this);
    }

    public function edit()
    {
        $this->checkEditActionStatus();

        if ($this->data->password ?? null) {
            $this->data->password = "";
        }

        if (view()->exists("{$this->viewPrefix}.form")) {
            return view("{$this->viewPrefix}.form", (array)$this);
        }

        return view('crud.form', (array)$this);
    }

    public function update(UpdateRequest $request)
    {
        $files = [];

        $this->checkEditActionStatus();
        $this->validate($request, $this->getValidationRules());

        foreach ($this->formItems as $key => $item) {
            if ($request->file($key)) {
                if (@$item[1] == 'image') {
                    $files[$key] = saveFile($request->file($key), $this->uploadPath);
                } elseif (@$item[1] == 'file') {
                    $files[$key] = saveFile($request->file($key), $this->uploadPath);
                } elseif (@$item[1] == 'video') {
                    $files[$key] = saveFile($request->file($key), $this->uploadPath);
                }
            }
        }

        $request->request->add($files);

        $inputs = $request->input();

        foreach ($inputs as $key => $val) {
            $inputs[$key] = $val ?: (($val === 0) ? 0 : null);
        }

        if (@$this->data->update($inputs)) {
            try {
                foreach ($this->formHasParents as $key => $val) {
                    $joinModel = $this->model->getFormRelationalModel($key);

                    if ($joinModel->getJoinType() == 'manyToMany') {
                        $this->data->{$key}()->sync($request->input($key));
                    } elseif ($joinModel->getJoinType() == 'oneToMany') {
                        $key                        = Str::singular($key);
                        $this->data->{$key . "_id"} = @$inputs[$key . "_id"];
                        $this->data->save();
                    }
                }
            } catch (Exception $e) {
                Log::error("CRUD::UPDATE_ERROR, error: {$e->getMessage()}");
            }

            $this->actionMessage = ['success' => 'Item updated successfully !'];
        } else {
            $this->actionMessage = ['error' => 'Item updating Failed !'];

            foreach ($files as $file) {
                @unlink($this->uploadPath . '/' . $file);
            }
        }

        if ($this->redirectAfterAction) {
            $this->redirectToSingleView();
        }
    }

    public function destroy()
    {
        $this->checkDeleteActionStatus();

        if ($this->data) {
            $images = [];

            foreach ((array)@$this->model->getFormInputTypes() as $key => $item) {
                if (in_array($key, ['file', 'image', 'video'])) {
                    $images[] = $item;
                }
            }

            if ($this->data->delete()) {
                foreach ($images as $image) {
                    @unlink($this->uploadPath . '/' . $image);
                }

                $this->actionMessage = ['success' => 'Item Deleted successfully!'];
            } else {
                $this->actionMessage = ['error' => 'Item Deleting Failed !'];
            }
        }

        return redirect()->route($this->routePrefix . '.index', request()->only(['page', 'query']))->with($this->actionMessage);
    }
}