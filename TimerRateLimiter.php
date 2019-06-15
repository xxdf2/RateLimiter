<?php

/**
 * 基于滑动时间窗口的限流
 * Class TimerRateLimiter
 */
class TimerRateLimiter
{
    private $redis;

    //限流key
    private $limit_key;

    //时间范围内
    private $period;

    //时间范围内允许发生的次数
    private $total;

    public function __construct($limit_key,$period,$total)
    {
        $this->redis=new Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->limit_key=$limit_key;
        $this->period=$period;
        $this->total=$total;
    }

    /**
     * 检测是否限流
     * @return bool
     */
    public function checkLimit()
    {
        $now_ts=round(microtime(true) * 1000);

        //删除时间窗口之外的数据
        $this->redis->zRemRangeByScore($this->limit_key,0,$now_ts-$this->period*1000);

        //计算时间窗口内的数据量
        $count=$this->redis->zCard($this->limit_key);

        //60秒过期，防止冷数据
        $this->redis->expire($this->limit_key,$this->period+1);

        //如果超出
        if($count>=$this->total){
            return true;
        }

        //请求成功后记录每一次行为（score是行为发生的时间）
        $this->redis->zAdd($this->limit_key,$now_ts,$now_ts);//删除60秒之前的行为

        return false;
    }
}
