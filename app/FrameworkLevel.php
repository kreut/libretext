<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FrameworkLevel extends Model
{

    /**
     * @param $framework_id
     * @return array
     * @throws Exception
     */
    public function getAllChildren($framework_id): array
    {
        ///that level and all of its children you need to take the level and subtract the difference from it
        $children = [];
        switch ($this->level) {
            case(4):
                break;
            case(3):
                $level_4_children = $this->getChildren($framework_id, $this->id);
                foreach ($level_4_children as $child) {
                    $children[] = $child;
                }
                break;
            case(2):
                $level_3_children = $this->getChildren($framework_id, $this->id);
                foreach ($level_3_children as $level_3_child) {
                    $children[] = $level_3_child;
                    $level_4_children = $this->getChildren($framework_id, $level_3_child->id);
                    foreach ($level_4_children as $level_4_child) {
                        $children[] = $level_4_child;
                    }
                }
                break;
            case(1):
                $level_2_children = $this->getChildren($framework_id, $this->id);
                foreach ($level_2_children as $level_2_child) {
                    $children[] = $level_2_child;
                    $level_3_children = $this->getChildren($framework_id, $level_2_child->id);
                    foreach ($level_3_children as $level_3_child) {
                        $children[] = $level_3_child;
                        $level_4_children = $this->getChildren($framework_id, $level_3_child->id);
                        foreach ($level_4_children as $level_4_child) {
                            $children[] = $level_4_child;
                        }
                    }
                }
                break;
            default:
                throw new Exception("Not a valid move to level");
        }
        $children_ids = [];
        foreach ($children as $child) {
            $children_ids[] = $child['id'];
        }
        return array_unique($children_ids);
    }

    /**
     * @param int $framework_id
     * @param int $level
     * @return array
     */
    function getByLevel(int $framework_id, int $level): array
    {
        $levels = $this->where('framework_id', $framework_id)
            ->where('level', $level)
            ->orderBy('order')
            ->get();
        $levels_by_id = [];
        foreach ($levels as $level) {
            $levels_by_id[$level->id] = $level;
        }
        return $levels_by_id;
    }

    /**
     * @param int $framework_id
     * @param int $level
     * @param int $parent_id
     * @param string $title
     * @return mixed
     */

    public function getFrameworkLevelbyCurrentLevelParentAndTitle(int $framework_id, int $level, int $parent_id, string $title)
    {
        return $this->where('framework_id', $framework_id)
            ->where('level', $level)
            ->where('parent_id', $parent_id)
            ->where('title', $title)
            ->first();
    }

    /**
     * @param $framework_id
     * @param $level
     * @param $parent_id
     * @param $title
     * @return mixed
     */
    public function titleMatchByLevelAndParent($framework_id, $level, $parent_id, $title)
    {
        return $this->where('framework_id', $framework_id)
            ->where('level', $level)
            ->where('parent_id', $parent_id)
            ->where(DB::raw("LOWER(title)"), strtolower($title))
            ->first();
    }

    /**
     * @param $framework_id
     * @param $parent_id
     * @return int
     */
    function getMaxOrderPlusOne($framework_id, $parent_id): int
    {
        return 1 + $this->where('framework_id', $framework_id)
                ->where('parent_id', $parent_id)
                ->max('order');
    }

    public function getChildren(int $framework_id, int $parent_id)
    {
        return $this->where('framework_id', $framework_id)
            ->where('parent_id', $parent_id)
            ->orderBy('order')
            ->get();
    }

    public function reKeyOrder()
    {
        $framework_levels = $this
            ->where('framework_id', $this->framework_id)
            ->where('parent_id', $this->parent_id)
            ->orderBy('order', 'ASC')
            ->orderBy('updated_at', 'ASC')
            ->get();
        foreach ($framework_levels as $key => $framework_level) {
            $this->order = $key + 1;
            $this->save();
        }
    }


}
