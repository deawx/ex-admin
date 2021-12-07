<?php


namespace app\model;

use Eadmin\Admin;
use Eadmin\support\Token;
use think\db\BaseQuery;
use think\db\Query;
use think\facade\Request;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * Class BaseModel
 * @package app\model
 * @method $this uid() uid查询条件
 * @method $this pages() 分页条件
 * @method $this show() 显示状态 status 1
 * @method $this hide() 隐藏状态 status 0
 * @method $this distanceOrder(string $sql, int $position = 0, string $distanceType = 'asc') 距离排序
 * @method $this distanceField(string $lat, string $lng, string $field) 距离计算
 */
class BaseModel extends Model
{
    use SoftDelete;
    //软删除字段
    protected $deleteTime = 'delete_time';

    //软删除默认值
    protected $defaultSoftDelete = 0;

    //数据权限字段
    protected $dataAuth = [];

    protected $autoWriteTimestamp = 'datetime';
    protected $globalScope = ['base'];
    // +----------------------------------------------------------------------------------------------------------------
    // | 基类                                                                                                           |
    // +----------------------------------------------------------------------------------------------------------------

    public function scopeBase($query)
    {
        $id = $query->getPk();
        $tableFields = $query->getTableFields();
        //默认排序
        if (in_array('sort', $tableFields)) {
            $query->order('sort asc')->order("{$id} desc");
        } else {
            $query->order("{$id} desc");
        }
        Admin::auth()->checkDataAuth($this->dataAuth,$query);
    }

    // +----------------------------------------------------------------------------------------------------------------
    // | 搜索条件封装                                                                                                     |
    // +----------------------------------------------------------------------------------------------------------------

    //分页条件
    public function scopePages($query,$page=1,$size=10)
    {
        $page = Request::param('page', $page);
        $size = Request::param('size', $size);
        $query->page($page, $size);
    }

    //查询当前用户条件
    public function scopeUid($query)
    {
        $query->where('uid', Token::id());
    }

	// 显示状态的
	public function scopeShow($query)
	{
		$query->where('status', 1);
	}
    // 隐藏状态的
    public function scopeHide($query)
    {
        $query->where('status', 0);
    }


    /**
     * 生成距离条件sql (米)
     * @param $query
     * @param string $lat 经纬度
     * @param string $lng 经纬度
     * @param string $field 字段 跟withCount传入的自定义字段失效
     */
    public function scopeDistanceField($query, $lat, $lng, $field = '*')
    {
        $options = $query->getOptions();
        if ($field == '*' && isset($options['field']) && in_array('*', $query->getOptions()['field'])) {
            $field = '';
        } else {
            $field .= ',';
        }
        $field = "{$field} ROUND(
                6378.138 * 2 * ASIN(
                    SQRT(
                        POW(
                            SIN(
                                (
                                    {$lat} * PI() / 180 - lat * PI() / 180
                                ) / 2
                            ),
                            2
                        ) + COS({$lat} * PI() / 180) * COS(lat * PI() / 180) * POW(
                            SIN(
                                (
                                     {$lng} * PI() / 180 -   lng * PI() / 180
                                ) / 2
                            ),
                            2
                        )
                    )
                ) * 1000
            ) AS distance";
        $query->field($field);
    }

    // +----------------------------------------------------------------------------------------------------------------
    // | 排序封装                                                                                                        |
    // +----------------------------------------------------------------------------------------------------------------

    /**
     * 距离排序
     * @param $query
     * @param string $sql 按距离的排序后的其他条件
     * @param int $position 位置 0.距离在前 其他筛选条件在后  1.距离在后  其他筛选条件在前
     * @param string $distanceType 距离升序 asc（默认） 距离降序 desc
     */
    public function scopeDistanceOrder($query, $sql = '', $position = 0, $distanceType = 'asc')
    {
        $order = "distance {$distanceType}";
        if (!empty($sql)) $order = $position == 0 ? "$order, $sql" : "{$sql}, $order";
        $query->removeOption('order')->order($order);
    }

    public function withNoTrashed(Query $query): void
    {
        $tableFields = $query->getTableFields();

        if (in_array($this->deleteTime, $tableFields)) {
            $field = $this->getDeleteTimeField(true);

            if ($field) {
                $condition = is_null($this->defaultSoftDelete) ? ['null', ''] : ['=', $this->defaultSoftDelete];
                $query->useSoftDelete($field, $condition);
            }
        }
    }

    // +----------------------------------------------------------------------------------------------------------------
    // | 模型封装                                                                                                        |
    // +----------------------------------------------------------------------------------------------------------------

    // 关联用户表
    public function user(){
        return $this->belongsTo(User::class,'uid');
    }

}
