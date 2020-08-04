<?php

namespace App\Http\Controllers\Service;
use App\Common\Http;
use App\Models\Base\BaseMatchModel;
use App\Models\Base\BaseMatchResultModel;
use App\Models\V1\MatchModel;

class Match
{
    public static function create_compass_angle($infile,$outfile,$compassVersion=0){
        $http   = new Http();
        $url    = config('app.matlabhost').'/compass';
        //$url    = "http://localhost:5000/compass";
        //$url    = "http://dev1.api.launchever.cn/api/matchCaculate/upload";
        $data   = file_get_contents($infile);
        $data   = trim($data);
        $md5    = md5($data);
        $res    = $http->url($url)
            ->method("post")
            ->set_data(["compassVersion"=>$compassVersion,"compassSensorData"=>$data,'md5'=>$md5])
            ->send();
        $res = \GuzzleHttp\json_decode($res);

        if($res->code == 200)
        {
            file_put_contents($outfile,$res->data);
        }

        return $res->code == 200 ? true : false;
    }

    /**
     * 计算比赛的经验值
     * @param $matchId 比赛ID
     * @return float
     */
    public static function calculate_empiric($matchId) {
        $matchInfo = BaseMatchResultModel::where('match_id', $matchId)->select('grade_run','grade_touchball_num','grade_shoot','grade_speed','grade_defense','grade_dribble')->first()->toArray();
        $match = BaseMatchModel::find($matchId);
        $time = $match->time_length / 3600;
        $grade = array_sum($matchInfo);
        //echo $time;exit;
        return  round(($grade / count($matchInfo)) / 100  * $time,2);
    }

    /**
     * 获取比赛评论
     * @param $types array distance|pass|shoot|run|speed|position
     * @return array
     */
    public static function get_comments($types) {
        $stages = [0,20,40,60,80];
        foreach($types as $type => $grade) {
            if($type != 'position') {
                $key = 0;
                for($i=0;$i<5;$i++){
                    if($grade >= $stages[$i]) {
                        $key = $i;
                    }
                }
                $types[$type] = $key;
            }
        }

        $LBRB = ['后场边路防守的专家，应对敌人一次次的冲击。','适当参与进攻，提高球队进攻效率'];
        $LMFRMF = ['边路杀手，是插入敌人后方的尖刀','回防倦怠，前场时间太长，无法更好的接应队友传球以及协助防守'];
        $SS_AMF = ['柱式中锋的典型，是球队进攻的良好前锋','回防不够积极，可能会影响全队防守质量'];
        $MF = ["边路进攻的策应者，在中路难以打开局面时帮助球队获得更多进攻手段","可以适当前插增强进攻效率"];//中锋
        $LRMF =["边路进攻的策应者，在中路难以打开局面时帮助球队获得更多进攻手段","可以适当前插增强进攻效率"];
        $CB = ['后场防守的核心，抵御敌人进攻的最前线。','适当前插接应中场球员，增加组织效率'];

        $positions = [
            'LWF'=> $LMFRMF,    //前边
            'RwF'=> $LMFRMF,    //前边
            'SS'=> $SS_AMF,     //前中
            'AMF'=> $MF,//AMF CMF DMF
            'CMF'=> $MF,
            'DMF'=> $MF,
            'LMF'=> $LRMF,//中边锋
            'RMF'=> $LRMF,
            'LB'=> $LBRB,//后边
            'RB'=> $LBRB,
            'CB'=> $CB
        ];

        $grades = [
            'distance' => [
                '更多的跑动才能带来更多的机会，在球场散步会让队友承担太多责任。',
                '增强跑动距离可以覆盖更多面积，减小队友压力。',
                '跑动距离达到了平均水平，继续保持努力提升自己。',
                '超出了大部分用户的跑动距离，覆盖了前后场大面积区域让身边的伙伴可以轻松很多的队友。',
                '跑不死应该就是形容您的，是对手的梦魇队友的福音，全场均可见到你的身影，对团队贡献莫大。'
            ],
            'pass' => [
                '不要做球场的独狼，多传球会使你踢起来更加轻松并且使球队有更好的成绩。',
                '球跑的比人快，尽量多传球给队友，信任他们，才能获得更多认可。',
                '继续努力，一个合格的球员是从良好的传球开始。',
                '拥有协作精神，更多的传球能有效牵制对手让他们疲于奔命，干的漂亮！',
                '看来你就是球队的核心，你的队友都信任你，而你也反哺队友，通过优秀的传球让他们感受到团队竞技的魅力！'
            ],
            'shoot' => [
                '需要更多的把握射门的机会，机会不会从天上掉下来。',
                '继续提升对射门的感觉，让你可以有更好的射门机会和得分能力。',
                '进一步增加射门技巧可以协助球队获胜。',
                '进攻的核心，队友信任你，让你可以放手将每一次不可能的机会变为可能得分的射门机会。继续努力，成为让他人胆寒的进攻手。',
                '进攻的核心，队友信任你，让你可以放手将每一次不可能的机会变为可能得分的射门机会。继续努力，成为让他人胆寒的进攻手。'
            ],
            'run' => [
                '您的体能有待提高，不要让体能成为您发挥水平的瓶颈。',
                '您的体能有待提高，不要让体能成为您发挥水平的瓶颈。',
                '您的体能状况良好，请再接再厉努力提高。',
                '您的体能极佳，犹如跑不死的内德维德一般成为对手的梦魇。',
                '您的体能极佳，犹如跑不死的内德维德一般成为对手的梦魇。'
            ],
            'speed' => [
                '或许速度并不是您的长处，用头脑和嗅觉弥补缺点。',
                '或许速度并不是您的长处，用头脑和嗅觉弥补缺点。',
                '',
                '风一般的速度让所有的对手望尘莫及。',
                '风一般的速度让所有的对手望尘莫及。'
            ],
            'position' => $positions
        ];

        $comments = [];
        foreach($types as $type => $key) {
            if(isset($grades[$type]) && isset($grades[$type][$key]) ){
                if($type == 'position') {
                    $comments[] = $grades[$type][$key][0];
                } else{
                    $comments[] = $grades[$type][$key];
                }
            }
        }

        return array_filter($comments);
    }
}
