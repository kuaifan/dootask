<?php

namespace App\Services\WebDav;

use App\Models\User;
use App\Models\WebDavProperty;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\PropertyStorage\Backend\BackendInterface;
use Sabre\DAV\Xml\Property\Complex;

class WebDavPropertyBackend implements BackendInterface
{
    public function __construct(private User $user)
    {
    }

    public function propFind($path, PropFind $propFind)
    {
        if (!$propFind->isAllProps() && count($propFind->get404Properties()) === 0) return;
        $rows = WebDavProperty::whereUserid($this->user->userid)
            ->wherePathHash(hash('sha256', $path))->get();
        foreach ($rows as $row) {
            $value = $row->value_type === 2 ? new Complex($row->value) : $row->value;
            $propFind->set($row->name, $value);
        }
    }

    public function propPatch($path, PropPatch $propPatch)
    {
        $propPatch->handleRemaining(function (array $properties) use ($path) {
            foreach ($properties as $value) {
                if ($value !== null && !$value instanceof Complex && !is_scalar($value)) {
                    return false;
                }
            }
            foreach ($properties as $name => $value) {
                $where = [
                    'userid' => $this->user->userid,
                    'path_hash' => hash('sha256', $path),
                    'property_hash' => hash('sha256', $name),
                ];
                if ($value === null) {
                    WebDavProperty::where($where)->delete();
                    continue;
                }
                if ($value instanceof Complex) {
                    $valueType = 2;
                    $value = $value->getXml();
                } else {
                    $valueType = 1;
                    $value = (string) $value;
                }
                WebDavProperty::updateInsert($where, [
                    'path' => $path,
                    'name' => $name,
                    'value_type' => $valueType,
                    'value' => $value,
                ]);
            }
            return true;
        });
    }

    public function delete($path)
    {
        $rows = WebDavProperty::whereUserid($this->user->userid)->get();
        foreach ($rows as $row) {
            if ($row->path === $path || str_starts_with($row->path, rtrim($path, '/') . '/')) {
                $row->delete();
            }
        }
    }

    public function move($source, $destination)
    {
        $rows = WebDavProperty::whereUserid($this->user->userid)->get();
        foreach ($rows as $row) {
            if ($row->path !== $source && !str_starts_with($row->path, rtrim($source, '/') . '/')) continue;
            $suffix = substr($row->path, strlen($source));
            $row->path = $destination . $suffix;
            $row->path_hash = hash('sha256', $row->path);
            $row->save();
        }
    }
}
