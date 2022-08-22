<?php

namespace vpashkov\represent\laravel;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller;
use vpashkov\represent\helpers\H;
use vpashkov\represent\core\Represent;


class LaravelRepresentController extends Controller
{
    public string $representName;
    public bool $collectDicts = true;
    public bool $collectCount = true;
    public bool $collectMeta = false;
    public array $params;
    public ?string $dictName;
    public Represent $represent;

    public function __construct()
    {
        $this->middleware(function ($request, $next)
            {
            $this->collectParams($request);

            return $next($request);
            });

    }

    public function collectParams($request)
    {
        $params = $request->all();
        if (H::get($params, 'represent') === null) {
            $this->representName = implode('/', [$request->route('module'), $request->route('model'), $request->route('action')]);
        } else {
            $this->representName = H::get($params, 'represent');
        }
        $this->collectDicts = H::get($params, 'dicts', 'true') === 'true' ? true : false;
        $this->dictName = H::get($params, 'dict', null);

        $this->params = $params === null ? [] : $params;
        $this->represent = LaravelRepresent::byName($this->representName, $this->params);
        if ($this->represent->private === true) {
            throw new HttpResponseException(response()->json(['error' => 'Not found'], 404));
        }
    }

    public function one()
    {
        $response = [];
        $response['data'] = $this->represent->one();
        if ($this->collectDicts) {
            $response['dicts'] = $this->represent->dicts();
        }

        return $response;
    }

    public function all()
    {
        $response = [];
        $response['data'] = $this->represent->all();
        if ($this->collectCount) {
            $response['count'] = $this->represent->count();
        }
        if ($this->collectDicts) {
            $response['dicts'] = $this->represent->dicts();
        }
        if ($this->collectMeta) {
            $response['meta'] = $this->represent->meta();
        }

        return $response;
    }

    public function save()
    {
        $status = ['status' => 'fail'];
        if (array_key_exists('rows', $this->params)) {
            $status = $this->represent->saveAll($this->params['rows']);
        } elseif (array_key_exists('row', $this->params)) {
            $status = $this->represent->saveOne($this->params['row']);
        }

        return $status;
    }

    public function delete()
    {
        $status = ['status' => 'fail'];

        if (array_key_exists('rows', $this->params)) {
            $status = $this->represent->deleteAll($this->params['rows']);
        } elseif (array_key_exists('row', $this->params)) {
            $status = $this->represent->deleteOne($this->params['row']);
        }

        return $status;
    }

    public function dicts()
    {
        return $this->represent->dicts();
    }

    public function dict()
    {
        return $this->represent->dict($this->dictName);
    }

    public function meta()
    {
        return $this->represent->meta();
    }

    public function count()
    {
        return $this->represent->count();
    }


}

