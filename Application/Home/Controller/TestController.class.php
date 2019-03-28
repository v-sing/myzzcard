<?php

namespace Home\Controller;

use Think\Controller;
use Think\Model;
use Org\Util\Des;

class TestController extends CoinController
{
    public $city;
    public $city1;
    public $country;

    public function _initialize()
    {
        $this->city    = $this->city();
        $this->city1   = $this->city1();
        $this->country = $this->country();
        parent::_initialize();
    }

    public function city()
    {
        $city = array(
            "安阳市安阳县"   => array("水冶镇", "铜冶镇", "铜冶镇", "白壁镇", "善应镇", "曲沟镇", "柏庄镇", "吕村镇", "磊口乡", "马家乡", "蒋村乡", "永和乡", "瓦店乡", "伦掌乡", "安丰乡", "洪河屯乡", "韩陵乡", "崔家桥乡", "许家沟乡", "辛村乡", "北郭乡"),
            "安阳市滑县"    => array("道口镇", "城关镇", "城关镇", "牛屯镇", "留固镇", "上官镇", "万古镇", "白道口镇", "高平镇", "王庄镇", "四间房乡", "八里营乡", "赵营乡", "瓦岗寨乡", "大寨乡", "桑村乡", "老店乡", "半坡店乡", "慈周寨乡", "焦虎乡", "小铺乡"),
            "安阳市林州市"   => array("临淇镇", "姚村镇", "姚村镇", "河顺镇", "任村镇", "东姚镇", "合涧镇", "横水镇", "陵阳镇", "原康镇", "五龙镇", "采桑镇", "东岗镇", "桂林镇", "石板岩乡", "茶店乡"),
            "安阳市龙安区"   => array("龙泉镇", "马投涧乡", "马投涧乡", "东风乡"),
            "安阳市内黄县"   => array("城关镇", "东庄镇", "东庄镇", "井店镇", "楚旺镇", "后河镇", "梁庄镇", "马上乡", "高堤乡", "张龙乡", "豆公乡", "田氏乡", "石盘屯乡", "宋村乡", "中召乡", "六村乡", "二安乡", "亳城乡"),
            "安阳市汤阴县"   => array("城关镇", "宜沟镇", "宜沟镇", "菜园镇", "五陵镇", "任固镇", "白营乡", "古贤乡", "瓦岗乡", "伏道乡"),
            "安阳市文峰区"   => array("宝莲寺镇", "高庄乡", "高庄乡"),
            "安阳市殷都区"   => array("西郊乡"),
            "鹤壁市鹤山区"   => array("鹤壁集乡", "姬家山乡", "姬家山乡"),
            "鹤壁市浚县"    => array("城关镇", "善堂镇", "善堂镇", "小河镇", "新镇镇", "屯子镇", "钜桥镇", "黎阳镇", "卫贤镇", "白寺乡", "王庄乡"),
            "鹤壁市淇滨区"   => array("大赉店镇", "大河涧乡", "大河涧乡", "上峪乡"),
            "鹤壁市淇县"    => array("朝歌镇", "高村镇", "高村镇", "北阳镇", "西岗乡", "庙口乡", "桥盟乡", "黄洞乡"),
            "鹤壁市山城区"   => array("鹿楼乡", "石林乡", "石林乡"),
            "焦作市博爱县"   => array("清化镇", "许良镇", "许良镇", "月山镇", "阳庙镇", "柏山镇", "磨头镇", "孝敬镇", "苏家作乡", "寨豁乡"),
            "焦作市孟州市"   => array("谷旦镇", "南庄镇", "南庄镇", "城伯镇", "西虢镇", "化工镇", "赵和镇"),
            "焦作市沁阳市"   => array("崇义镇", "柏香镇", "柏香镇", "山王庄镇", "紫陵镇", "西向镇", "西万镇", "王曲乡", "常平乡"),
            "焦作市温县"    => array("北冷乡", "温泉镇", "温泉镇", "赵堡镇", "祥云镇", "黄庄镇", "武德镇", "番田镇", "南张羌镇", "招贤乡"),
            "焦作市武陟县"   => array("木城镇", "詹店镇", "詹店镇", "西陶镇", "龙源镇", "宁郭镇", "大封镇", "谢旗营镇", "大虹桥乡", "圪垱店乡", "乔庙乡", "三阳乡", "北郭乡", "小董乡"),
            "焦作市修武县"   => array("城关镇", "方庄镇", "方庄镇", "郇封镇", "高村乡", "西村乡", "岸上乡", "五里源乡"),
            "开封市金明区"   => array("杏花营镇", "西郊乡", "西郊乡"),
            "开封市开封县"   => array("城关镇", "朱仙镇", "朱仙镇", "陈留镇", "八里湾镇", "仇楼镇", "曲兴镇", "半坡店乡", "西姜寨乡", "兴隆乡", "刘店乡", "袁坊乡", "范村乡", "杜良乡", "万隆乡"),
            "开封市兰考县"   => array("城关镇", "堌阳镇", "堌阳镇", "张君墓镇", "南彰镇", "红庙镇", "阎楼乡", "谷营乡", "小宋乡", "城关乡", "许河乡", "孟寨乡", "三义寨乡", "爪营乡葡萄架乡", "东坝头乡"),
            "开封市龙亭区"   => array("水稻乡", "北郊乡", "北郊乡", "柳园口乡"),
            "开封市顺河区"   => array("东郊乡", "土柏岗乡", "土柏岗乡"),
            "开封市通许县"   => array("城关镇", "朱砂镇", "朱砂镇", "玉皇庙镇", "四所楼镇", "长智镇", "竖岗镇", "练城乡", "冯庄乡", "厉庄乡", "大岗李乡", "孙营乡", "邸阁乡"),
            "开封市尉氏县"   => array("城关镇", "洧川镇", "洧川镇", "蔡庄镇", "永兴镇", "张市镇", "朱曲镇", "十八里镇", "水坡镇", "岗李乡", "大营乡", "庄头乡", "大桥乡", "南曹乡", "邢庄乡", "门楼任乡", "小陈乡"),
            "开封市禹王台"   => array("南郊乡 "),
            "洛阳市栾川县"   => array("城关镇", "潭头镇", "潭头镇", "三川镇", "冷水镇", "陶湾镇", "合峪镇", "赤土店镇", "庙子乡", "白土乡", "叫河乡", "石庙乡", "秋扒乡", "狮子庙乡"),
            "洛阳市洛宁县"   => array("城关镇", "王范回族镇", "王范回族镇", "上戈镇", "河底乡", "东宋乡", "小界乡", "马店乡", "长水乡", "兴华乡", "罗岭乡", "下峪乡", "故县乡", "西山底乡", "涧口乡", "赵村乡", "底张乡", "陈吴乡"),
            "洛阳市孟津县"   => array("城关镇", "平乐镇", "平乐镇", "会盟镇", "白鹤镇", "麻屯镇", "横水镇", "朝阳镇", "马屯镇", "送庄镇"),
            "洛阳市汝阳县"   => array("城关镇", "上店镇", "上店镇", "小店镇", "付店镇", "靳村乡", "王坪乡", "三屯乡", "刘店乡", "陶营乡", "内埠乡", "蔡店乡", "十八盘乡"),
            "洛阳市嵩县"    => array("白河乡", "城关镇", "城关镇", "田湖镇", "车村镇", "旧县镇", "大坪乡", "库区乡", "何村乡", "德亭乡", "大章乡", "纸房乡", "饭坡乡", "九店乡", "黄庄乡", "木植街乡"),
            "洛阳市新安县"   => array("城关镇", "石寺镇", "石寺镇", "铁门镇", "磁涧镇", "五头镇", "仓头乡", "北冶乡", "曹村乡", "正村乡", "南李村乡"),
            "洛阳市偃师市"   => array("城关镇", "首阳山镇", "首阳山镇", "诸葛镇", "顾县镇", "高龙镇", "寇店镇", "府店镇", "庞村镇", "岳滩镇", "李村镇", "翟镇镇", "缑氏镇", "佃庄镇", "邙岭乡", "大口乡"),
            "洛阳市伊川县"   => array("城关镇", "呜皋镇", "呜皋镇", "水寨镇", "彭婆镇", "高山乡", "平等乡", "酒后乡", "葛寨乡", "白元乡", "白沙乡", "半坡乡", "江左乡", "吕店乡"),
            "洛阳市宜阳县"   => array("城关镇", "丰李镇", "丰李镇", "柳泉镇", "白杨镇", "韩城镇", "寻村镇；城关乡", "盐镇乡", "莲庄乡", "赵堡乡", "董王庄乡", "樊村乡", "高村乡", "三乡乡张坞乡", "穆册乡", "上观乡"),
            "漯河市临颍县"   => array("城关镇", "繁城回族镇", "繁城回族镇", "王岗镇", "台陈镇", "瓦店镇", "杜曲镇", "巨陵镇", "窝城镇", "三家店镇", "石桥乡", "王孟乡", "陈庄乡", "皇帝庙乡", "大郭乡"),
            "漯河市舞阳县"   => array("舞泉镇", "太尉镇", "太尉镇", "莲花镇", "吴城镇", "孟寨镇", "辛安镇", "北舞渡镇", "保和乡", "九街乡姜店乡", "马村乡", "侯集乡", "章化乡"),
            "漯河市郾城区"   => array("城关镇", "商桥镇", "商桥镇", "孟庙镇", "龙城镇", "新店镇", "裴城镇", "黑龙潭乡", "李集乡"),
            "漯河市源汇区"   => array("大刘镇", "问十乡", "问十乡", "阴阳赵乡", "空冢郭乡"),
            "漯河市召陵区"   => array("召陵镇", "邓襄镇", "邓襄镇", "老窝镇", "万金镇", "青年村乡", "后谢乡", "姬石乡"),
            "南阳市邓州市"   => array("穰东镇", "急滩镇", "急滩镇", "孟楼镇", "林扒镇", "构林镇", "十林镇", "都司镇", "赵集镇", "张村镇", "刘集镇", "彭桥镇", "桑庄镇", "罗庄镇", "九龙乡", "裴营乡", "高集乡", "文渠乡", "陶营乡", "龙堰乡", "张楼乡"),
            "南阳市方城县"   => array("城关镇", "博望镇", "博望镇", "独树镇", "拐河镇", "赵河镇", "广阳镇", "小史店镇", "二朗庙乡", "古庄店乡", "柳河乡", "杨楼乡", "杨集乡", "四里店乡", "袁店回族乡", "清河乡"),
            "南阳市南召县"   => array("城关镇", "留山镇", "留山镇", "云阳镇", "南河店镇", "皇路店镇", "板山坪镇", "乔端镇", "白土岗镇", "四棵树乡", "皇后乡", "小店乡", "石门乡", "太山庙乡", "崔庄乡", "马柿坪乡"),
            "南阳市内乡县"   => array("城关镇", "夏馆镇", "夏馆镇", "师岗镇", "马山口镇", "湍东镇", "赤眉镇", "瓦亭镇", "灌涨镇", "王店镇", "板场乡", "七里坪乡", "余关乡", "赵店乡", "大桥乡"),
            "南阳市社旗县"   => array("社旗镇", "桥头镇", "桥头镇", "饶良镇", "兴隆镇", "晋庄镇", "郝寨镇", "苗店镇", "李店镇", "下洼乡", "太和乡", "城郊乡", "唐庄乡", "朱集乡", "大冯营乡"),
            "南阳市唐河县"   => array("源潭镇", "张店镇", "张店镇", "湖阳镇", "龙潭镇", "苍台镇", "桐寨铺镇", "郭滩镇", "黑龙镇", "少拜寺镇", "大河屯镇", "毕店镇", "上屯镇", "桐河乡", "昝岗乡", "马振抚乡", "祁仪乡", "古城乡", "东王集乡"),
            "南阳市桐柏县"   => array("城关镇", "月河镇", "月河镇", "固县镇", "毛集镇", "大河镇", "平氏镇", "埠江镇", "吴城镇", "淮源镇", "黄岗乡", "朱庄乡", "新集乡", "程湾乡", "安棚乡", "城郊乡"),
            "南阳市宛城区"   => array("瓦店镇", "官庄镇", "官庄镇", "红泥湾镇", "黄台岗镇", "汉冢乡", "金华乡", "溧河乡", "高庙乡", "茶庵乡", "新店乡"),
            "南阳市卧龙区"   => array("青华镇", "陆营镇", "陆营镇", "安皋镇", "蒲山镇", "石桥镇", "潦河镇", "英庄镇；王村乡", "七里园乡", "龙兴乡谢庄乡"),
            "南阳市西峡县"   => array("丹水镇", "西坪镇", "西坪镇", "桑坪镇", "米坪镇", "双龙镇", "回车镇", "丁河镇", "石界河乡", "阳城乡军马河乡", "二朗坪乡", "五里桥乡", "重阳乡", "太平镇乡", "寨根乡"),
            "南阳市淅川县"   => array("老城镇", "香花镇", "香花镇", "九重镇", "盛湾镇", "厚坡镇", "金河镇", "荆紫关镇", "上集镇", "马磴镇", "寺湾镇仓房镇", "毛堂乡", "大石桥乡", "滔河乡"),
            "南阳市新野县"   => array("城关镇", "王庄镇", "王庄镇", "沙堰镇", "新甸铺镇", "五星镇", "施庵镇", "歪子镇", "溧河铺镇", "王集镇", "前高庙乡", "樊集乡", "上庄乡", "上港乡"),
            "南阳市镇平县"   => array("城关镇", "侯集镇", "侯集镇", "晁陂镇", "贾宋镇", "枣园镇", "遮山镇", "石佛寺镇", "老庄镇", "卢医镇", "高丘镇曲屯镇", "杨营镇", "二龙乡", "张林乡", "马庄乡", "王岗乡", "柳泉铺乡", "安字营乡", "郭庄回族乡", "彭营乡"),
            "平顶山市宝丰县"  => array("城关镇", "大营镇", "大营镇", "石桥镇", "杨庄镇", "闹店镇", "周庄镇", "张八桥镇", "商酒务镇", "赵庄乡", "前营乡", "李庄乡"),
            "平顶山市郏县"   => array("城关镇", "冢头镇", "冢头镇", "安良镇", "堂街镇", "薛店镇", "长桥镇", "姚庄回族乡", "李口乡", "茨芭乡", "广阔天地乡", "黄道乡", "白庙乡", "渣园乡"),
            "平顶山市鲁山县"  => array("鲁阳镇", "下汤镇", "下汤镇", "梁洼镇", "张良镇", "张官营镇", "尧山镇", "辛集乡", "熊背乡", "瀼河乡", "瓦屋乡", "赵村乡", "董周乡", "仓头乡", "马楼乡", "鸡冢乡", "背孜乡", "磙子营乡", "观音寺乡", "四棵树乡", "昭平台库区乡"),
            "平顶山市汝州市"  => array("临汝镇镇", "寄料镇", "寄料镇", "温泉镇", "小屯镇", "杨楼乡", "庙下乡", "大峪乡", "尚庄乡", "王寨乡", "纸坊乡", "焦村乡", "蟒川乡", "陵头乡", "夏店乡"),
            "平顶山市舞钢市"  => array("尚店镇", "八台镇", "八台镇", "尹集镇", "庙街乡", "铁山乡", "武功乡", "杨庄乡"),
            "平顶山市叶县"   => array("昆阳镇", "任店镇", "任店镇", "保安镇", "遵化店镇", "仙台镇；城关乡", "邓李乡", "旧县乡", "龚店乡", "田庄乡", "夏李乡", "辛店乡", "龙泉乡", "廉村乡", "洪庄杨乡", "马庄回族乡", "水寨乡", "常村乡"),
            "濮阳市范县"    => array("城关镇", "濮城镇", "濮城镇", "辛庄乡", "杨集乡", "龙王庄乡", "白衣阁乡", "陆集乡", "张庄乡", "颜村铺乡", "高码头乡", "陈庄乡"),
            "濮阳市南乐县"   => array("城关镇", "韩张镇", "韩张镇", "元村镇", "千口乡", "张果屯乡", "谷金楼乡", "近德固乡", "杨村乡", "西邵乡", "寺庄乡", "梁村乡"),
            "濮阳市濮阳县"   => array("城关镇", "徐镇镇", "徐镇镇", "文留镇", "庆祖镇", "柳屯镇", "八公桥镇", "户部寨乡", "新习乡", "习城乡", "王称堌乡", "鲁河乡", "梁庄乡", "梨园乡", "五星乡", "白堽乡", "胡状乡", "清河头乡", "子岸乡", "渠村乡", "郎中乡"),
            "濮阳市清丰县"   => array("城关镇", "马庄桥镇", "马庄桥镇", "瓦屋头镇", "巩营乡", "固城乡", "柳格乡", "韩村乡", "双庙乡", "纸房乡", "仙庄乡", "大屯乡", "阳邵乡", "古城乡高堡乡", "马村乡", "大流乡"),
            "濮阳市台前县"   => array("城关镇", "侯庙镇", "侯庙镇", "马楼乡", "孙口乡", "打渔陈乡", "清水河乡", "夹河乡", "吴坝乡"),
            "三门峡市灵宝市"  => array("城关镇", "豫灵镇", "豫灵镇", "故县镇", "尹庄镇", "阳平镇", "朱阳镇", "焦村镇", "函谷关镇", "大王镇", "阳店镇川口乡", "苏村乡", "五亩乡", "寺河乡", "西阎乡"),
            "三门峡市卢氏县"  => array("城关镇", "五里川镇", "五里川镇", "朱阳关镇", "官坡镇", "杜关镇", "官道口镇", "东明镇", "范里镇", "横涧乡", "磨沟口乡", "双槐树乡", "汤河乡", "瓦窑沟乡", "狮子坪张", "徐家湾乡", "潘河乡", "木桐乡", "沙河乡"),
            "三门峡市渑池县"  => array("城关镇", "英豪镇", "英豪镇", "张村镇", "洪阳镇", "天池镇", "仁村乡", "果园乡", "陈村乡", "坡头乡", "段村乡", "南村乡"),
            "三门峡市陕县"   => array("大营镇", "原店镇", "原店镇", "观音堂镇", "西张村镇", "张湾乡", "菜园乡", "张茅乡", "王家后乡", "硖石乡", "西李村乡", "宫前乡", "店子乡"),
            "商丘市梁园区"   => array("双八镇", "谢集镇", "谢集镇", "张阁镇", "平台镇", "周集乡", "王楼乡", "孙福集乡", "水池铺乡", "刘口乡", "李庄乡"),
            "商丘市民权县"   => array("城关镇", "龙塘镇", "龙塘镇", "北关镇", "人和镇", "程庄镇", "王庄寨镇", "尹店乡", "双塔乡", "顺河乡褚庙乡", "野岗乡", "老颜集乡", "孙六乡", "胡集回族乡", "王桥乡", "林七乡", "伯党回族乡"),
            "商丘市宁陵县"   => array("城关回族镇", "柳河镇", "柳河镇", "张弓镇", "逻岗镇", "孔集乡", "刘楼乡", "黄岗乡", "阳驿乡", "城郊乡", "乔楼乡", "程楼乡", "石桥乡", "赵村乡"),
            "商丘市杞县"    => array("城关镇", "高阳镇", "高阳镇", "圉镇镇", "傅集镇", "葛岗镇", "阳堌镇", "五里河镇", "邢口镇", "宗店乡", "竹林乡", "板木乡", "官庄乡", "湖岗乡", "苏木乡", "沙沃乡", "平城乡", "泥沟乡", "西寨乡", "柿园乡", "裴村店乡"),
            "商丘市睢县"    => array("城关回族镇", "平岗镇", "平岗镇", "长岗镇", "湖庄镇", "西陵寺镇", "周堂镇", "蓼堤镇", "尚屯镇", "匡城乡", "后台乡", "河集乡", "白楼乡", "河堤乡", "白庙乡", "胡堂乡", "尤吉屯乡", "董店乡", "涧岗乡", "孙聚寨乡"),
            "商丘市睢阳区"   => array("李口镇", "宋集镇", "宋集镇", "郭村镇", "高辛镇", "勒马乡", "冯桥乡", "路河乡", "坞墙乡", "阎集乡娄店乡", "临河店乡", "毛堌堆乡", "包公庙乡"),
            "商丘市夏邑县"   => array("城关镇", "会亭镇", "会亭镇", "车站镇", "李集镇", "杨集镇", "马头镇", "韩道口镇", "济阳镇", "歧河乡火店乡", "中峰乡", "罗庄乡", "孔庄乡", "业庙乡", "何营乡", "骆集乡", "王集乡", "太平乡", "郭店乡", "桑堌乡", "曹集乡"),
            "商丘市永城市"   => array("城关镇", "芒山镇", "芒山镇", "高庄镇", "演集镇", "酇城镇", "薛湖镇", "马桥镇", "裴桥镇", "蒋口镇", "陈集镇", "十八里镇", "太丘乡", "酂阳乡", "龙岗乡", "顺和乡", "条河乡", "马牧乡", "刘河乡", "苗桥乡", "侯岭乡"),
            "商丘市虞城县"   => array("城关镇", "张集镇", "张集镇", "界沟镇", "贾寨镇", "谷熟镇", "利民镇", "大杨集镇", "杜集镇", "营廓镇", "田庙乡", "郑集乡", "刘集乡", "站集乡", "古王集乡", "李老家乡", "镇里堌乡", "稍岗乡", "城郊乡", "闻集乡乔集乡", "黄冢乡"),
            "商丘市柘城县"   => array("城关镇", "起台镇", "起台镇", "慈圣镇", "胡襄镇", "远襄镇", "安平镇", "陈青集镇", "张桥乡", "梁庄乡伯岗乡", "岗王乡", "申桥乡", "牛城乡", "惠济乡", "皇集乡", "李原乡", "老王集乡", "大仵乡", "马集乡", "洪恩乡"),
            "新乡市封丘县"   => array("城关镇", "黄陵镇", "黄陵镇", "黄德镇", "应举镇", "赵岗镇", "陈桥镇", "荆乡回族乡", "王村乡", "陈固乡", "居厢乡", "荆隆宫乡", "鲁岗乡", "李庄乡", "尹岗乡", "潘店乡", "留光乡", "曹岗乡", "冯村乡", "城关乡"),
            "新乡市凤泉区"   => array("大块镇", "耿黄乡", "耿黄乡", "潞王坟乡"),
            "新乡市红旗区"   => array("洪门镇", "关堤乡", "关堤乡"),
            "新乡市辉县市"   => array("峪河镇", "百泉镇", "百泉镇", "薄壁镇", "孟庄镇", "南寨镇", "上八里镇", "常村镇", "吴村镇", "赞城镇", "北云门镇", "南村镇", "高庄乡", "张村乡洪洲乡", "西平罗乡", "拍石头乡", "赵固乡", "沙窑乡", "冀屯乡", "黄水乡"),
            "新乡市获嘉县"   => array("城关镇", "亢村镇", "亢村镇", "中和镇", "照镜镇", "黄堤镇", "徐营镇", "冯庄镇", "史庄镇", "大新庄乡", "太山乡", "位庄乡"),
            "新乡市牧野区"   => array("王村镇", "牧野乡", "牧野乡"),
            "新乡市卫滨区"   => array("平原乡"),
            "新乡市卫辉市"   => array("汲水镇", "后河镇", "后河镇", "李源屯镇", "孙杏村镇", "太公泉镇", "上乐村镇", "唐庄镇", "庞寨乡", "柳庄乡", "安都乡", "狮豹头乡", "顿坊店乡", "城郊乡"),
            "新乡市新乡县"   => array("小冀镇", "大召营镇", "大召营镇", "翟坡镇", "七里营镇", "郎公庙镇", "古固寨镇", "合河乡"),
            "新乡市延津县"   => array("城关镇", "丰庄镇", "丰庄镇", "东屯镇", "小店镇", "石婆固乡", "魏邱乡", "司寨乡", "王楼乡", "马庄乡", "胙城乡", "榆林乡", "小潭乡", "僧固乡"),
            "新乡市原阳县"   => array("城关镇", "原武镇", "原武镇", "师寨镇", "福宁集乡", "祝楼乡", "桥北乡", "蒋庄乡", "官厂乡", "大宾乡", "陡门乡", "阳阿乡", "路寨乡", "韩董庄乡", "太平镇乡", "齐街乡", "靳堂乡", "葛埠口乡"),
            "新乡市长垣县"   => array("城关镇", "丁栾镇", "丁栾镇", "恼里镇", "魏庄镇", "樊相镇", "常村镇", "赵堤镇", "佘家乡", "张寨乡", "苗寨乡", "芦岗乡", "孟岗乡", "张三寨乡", "满村乡", "武邱乡", "方里乡"),
            "信阳市固始县"   => array("城关镇", "黎集镇", "黎集镇", "蒋集镇", "陈淋子镇", "郭陆滩镇", "往流镇", "方集镇", "胡族铺镇", "南大桥乡", "赵岗乡", "张老埠乡", "武庙集乡", "段集乡", "祖师庙乡", "草庙集乡", "沙河铺乡", "分水亭乡", "泉河铺乡", "陈集乡", "张广庙乡"),
            "信阳市光山县"   => array("弦山街道办事处", "紫水街道办事处", "紫水街道办事处", "十里镇", "寨河镇", "孙铁铺镇", "仙居乡", "北向店乡", "罗陈乡", "马畈镇", "殷棚乡", "文殊乡", "南向店乡", "晏河乡", "槐店乡", "泼陂河镇", "斛山乡", "砖桥镇", "凉亭乡", "白雀园镇"),
            "信阳市淮滨县"   => array("城关镇", "栏杆镇", "栏杆镇", "防胡镇", "新里镇", "马集镇", "期思镇", "赵集镇；台头乡", "王家岗乡", "固城乡", "三空桥乡", "张里乡", "芦集乡", "邓湾乡张庄乡", "王店乡", "谷堆乡"),
            "信阳市潢川县"   => array("踅孜镇", "双柳树镇", "双柳树镇", "卜塔集镇", "伞陂镇", "傅店镇", "仁和镇", "桃林铺镇", "江家集镇", "黄寺岗镇", "张集乡", "传流店乡", "来龙乡隆古乡", "谈店乡", "上油岗乡", "白店乡"),
            "信阳市罗山县"   => array("城关镇", "周党镇", "周党镇", "楠杆镇", "竹竿镇", "青山镇", "子路镇", "灵山镇", "彭新镇", "潘新镇", "高店乡", "尤店乡", "东铺乡", "莽张乡", "庙仙乡", "朱堂乡", "铁铺乡", "山店乡", "定远乡"),
            "信阳市平桥区"   => array("平桥镇", "明港镇", "明港镇", "李家寨镇", "东双河镇", "五里店镇", "邢集镇", "吴家店镇", "柳林乡", "洋河乡", "九店乡", "胡店乡", "肖店乡", "兰店乡", "王岗乡", "查山乡", "谭家河乡", "十三里桥乡", "彭家塆乡", "游河乡", "高梁店乡"),
            "信阳市商城县"   => array("城关镇", "上石桥镇", "上石桥镇", "双椿铺镇", "汪桥镇", "余集镇", "鄢岗镇", "酆集乡", "冯店乡", "李集乡", "鲇鱼山乡", "苏仙石乡", "汪岗乡", "吴河乡", "达权店乡", "长竹园乡", "河凤桥乡", "观庙乡", "伏山乡"),
            "信阳市浉河区"   => array("东双河镇", "李家寨镇", "李家寨镇", "吴家店镇", "谭家河乡", "十三里桥乡", "浉河港乡", "董家河乡", "游河乡"),
            "信阳市息县"    => array("城关镇", "包信镇", "包信镇", "夏庄镇", "东岳镇", "小茴店镇", "项店镇", "孙庙乡", "路口乡", "张陶乡", "彭店乡", "杨店乡", "白土店乡", "岗李店乡", "长陵乡", "陈棚乡", "临河乡", "关店乡", "曹黄林乡", "八里岔乡"),
            "信阳市新县"    => array("新集镇", "沙石镇", "沙石镇", "沙窝镇", "苏河乡", "千斤乡", "浒湾乡", "戴咀乡", "泗店乡", "田铺乡", "陈店乡", "卡房乡", "陡山河乡", "八里畈乡", "吴陈河乡", "郭家河乡", "箭厂河乡", "周河乡"),
            "许昌市襄城县"   => array("城关镇", "颍桥回族镇", "颍桥回族镇", "王洛镇", "麦岭镇", "颍阳镇", "紫云镇", "山头店乡", "湛北乡", "汾陈乡", "丁营乡", "双庙乡", "茨沟乡", "范湖乡", "姜庄乡", "十里铺乡"),
            "许昌市许昌县"   => array("蒋李集镇", "五女店镇", "五女店镇", "苏桥镇", "尚集镇", "将官池镇", "灵井镇", "张潘镇", "河街乡", "榆林乡", "陈曹乡", "邓庄乡", "小召乡", "艾庄回族乡", "长村张乡", "椹涧乡"),
            "许昌市鄢陵县"   => array("安陵镇", "柏梁镇", "柏梁镇", "马栏镇", "陈化店镇", "望田镇", "南坞乡", "张桥乡", "陶城乡", "彭店乡马坊乡", "大马乡"),
            "许昌市禹州市"   => array("神垕镇", "方山镇", "方山镇", "无梁镇", "火龙镇", "顺店镇", "鸿畅镇", "梁北镇", "古城镇", "文殊镇", "褚河乡", "郭连乡", "浅井乡", "张得乡", "朱阁乡", "苌庄乡", "花石乡", "鸠山乡", "磨街乡", "方岗乡", "小吕乡"),
            "许昌市长葛市"   => array("老城镇", "南席镇", "南席镇", "石固镇", "坡胡镇", "后河镇", "大周镇", "和尚桥镇", "董村镇", "石象乡古桥乡", "增福庙乡"),
            "郑州市登封市"   => array("卢店镇", "颍阳镇", "颍阳镇", "大冶镇", "大金店镇", "告成镇", "宣化镇", "石道乡", "君召乡", "东金店乡", "白坪乡", "唐庄乡"),
            "郑州市二七区"   => array("马寨镇", "侯寨乡", "侯寨乡"),
            "郑州市巩义市"   => array("回郭镇", "站街镇", "站街镇", "涉村镇", "米河镇", "小关镇", "鲁庄镇", "大峪沟镇", "西村镇", "芝田镇", "北山口镇", "新中镇", "夹津口镇", "康店镇", "竹林镇", "河洛镇"),
            "郑州市管城回族区" => array("十八里河镇", "南曹乡", "南曹乡", "圃田乡"),
            "郑州市惠济区"   => array("古荥镇", "花园口镇 ", "花园口镇 "),
            "郑州市金水区"   => array("祭城镇", "柳林镇", "柳林镇", "庙李镇 "),
            "郑州市上街区"   => array("峡窝镇"),
            "郑州市新密市"   => array("城关镇", "超化镇", "超化镇", "大隗镇", "米村镇", "平陌镇", "刘寨镇", "白寨镇", "岳村镇", "来集镇", "苟堂镇", "牛店镇"),
            "郑州市新郑市"   => array("和庄镇", "观音寺镇", "观音寺镇", "梨河镇", "龙湖镇", "薛店镇", "孟庄镇", "新村镇", "辛店镇", "郭店镇", "龙王乡", "城关乡"),
            "郑州市荥阳市"   => array("广武镇", "崔庙镇", "崔庙镇", "豫龙镇", "王村镇", "汜水镇", "高山镇", "刘河镇", "贾峪镇", "乔楼镇", "高村乡", "金寨回族乡"),
            "郑州市中牟县"   => array("城关镇", "官渡镇", "官渡镇", "白沙镇", "郑庵镇", "张庄镇", "韩寺镇", "万滩镇", "黄店镇", "九龙镇", "狼城岗镇", "大孟镇"),
            "郑州市中原区"   => array("须水镇", "石佛镇", "石佛镇", "沟赵乡"),
            "周口市郸城县"   => array("城关镇", "吴台镇", "吴台镇", "南丰镇", "白马镇", "宁平镇", "宜路镇", "钱店镇", "汲冢镇", "石槽镇", "虎岗乡", "汲水乡", "东风乡", "巴集乡", "李楼乡", "张完集乡", "丁村乡", "双楼乡", "秋渠乡", "胡集乡"),
            "周口市扶沟县"   => array("城关镇", "崔桥镇", "崔桥镇", "江村镇", "韭园镇", "包屯镇", "白潭镇", "练寺镇", "大新镇", "汴岗镇", "柴岗乡", "古城乡", "吕潭乡", "大李庄乡", "城郊乡"),
            "周口市淮阳县"   => array("城关回族镇", "新站镇", "新站镇", "鲁台镇", "临蔡镇", "安岭镇", "四通镇", "豆门乡", "白楼乡", "冯塘乡", "大连乡", "葛店乡", "王店乡", "曹河乡", "郑集乡", "许湾乡", "黄集乡", "齐老乡", "刘振屯乡"),
            "周口市鹿邑县"   => array("城关镇", "枣集镇", "枣集镇", "玄武镇", "试量镇", "辛集镇", "马铺镇", "王皮溜镇", "太清宫镇", "涡北镇", "穆店乡", "贾滩乡", "邱集乡", "观堂乡", "任集乡", "杨湖口乡", "唐集乡", "赵村乡", "郑家集乡", "生铁冢乡", "张店乡"),
            "周口市商水县"   => array("城关镇", "谭庄镇", "谭庄镇", "巴村镇", "白寺镇", "魏集镇", "黄寨镇", "邓城镇", "固墙镇", "练集镇", "胡吉镇", "化河乡", "平店乡", "袁老乡", "姚集乡", "舒庄乡", "郝岗乡", "张庄乡", "张明乡", "汤庄乡", "李埠口乡"),
            "周口市沈丘县"   => array("槐店回族镇", "留福镇", "留福镇", "刘庄店镇", "纸店镇", "新安集镇", "老城镇", "白集镇", "付井镇", "赵德营镇刘湾镇", "北郊乡", "范营乡", "李老庄乡", "北杨集乡", "周营乡", "洪山乡", "卞路口乡", "大邢庄乡", "冯营乡", "石槽集乡"),
            "周口市太康县"   => array("城关回族镇", "常营镇", "常营镇", "马头镇", "朱口镇", "老冢镇", "逊母口镇", "龙曲镇", "马厂镇", "板桥镇", "符草楼镇", "毛庄镇", "五里口乡", "大许寨乡", "独塘乡", "芝麻洼乡", "高贤乡", "王集乡", "转楼乡", "杨庙乡张集乡", "清集乡"),
            "周口市西华县"   => array("城关镇", "逍遥镇", "逍遥镇", "聂堆镇", "东夏亭镇", "西夏亭镇", "西华营镇", "红花集镇", "奉母镇", "址坊镇李大庄乡", "清河驿乡", "叶埠口乡", "艾岗乡", "黄桥乡", "田口乡", "迟营乡", "皮营乡", "大王庄乡", "东王营乡"),
            "周口市项城市"   => array("秣陵镇", "南顿镇", "南顿镇", "孙店镇", "贾岭镇", "新桥镇", "官会镇", "丁集镇", "李寨镇", "付集镇", "高寺镇", "王明口镇", "郑郭镇", "三张店乡", "永丰乡"),
            "驻马店市泌阳县"  => array("泌水镇", "羊册镇", "羊册镇", "沙河店镇", "板桥镇", "马谷田镇", "春水镇", "花园乡", "郭集乡", "象河乡", "王店乡", "付庄乡", "黄山口乡", "贾楼乡", "高店乡", "泰山庙乡", "下碑寺乡", "铜山乡", "高邑乡", "陈庄乡", "杨家集乡"),
            "驻马店市平舆县"  => array("古槐镇", "杨埠镇", "杨埠镇", "东和店镇", "西洋店镇", "庙湾镇", "射桥镇", "万金店乡", "李屯乡", "郭楼乡", "东皇庙乡", "高杨店乡", "玉皇庙乡", "十字路乡", "老王岗乡", "万冢乡", "后刘乡", "双庙乡"),
            "驻马店市确山县"  => array("盘龙镇", "任店镇", "任店镇", "留庄镇", "刘店镇", "竹沟镇", "蚁蜂镇", "新安店镇", "普会寺乡", "石滚河乡", "瓦岗乡", "李新店乡", "双河乡"),
            "驻马店市汝南县"  => array("灈阳镇", "车站镇", "车站镇", "玉山镇；阳丰乡", "花庄乡", "槐树乡", "沈寨乡", "和兴乡", "文城乡", "褚堂乡", "石寨铺乡", "嵖岈山乡", "常庄乡"),
            "驻马店市上蔡县"  => array("蔡都镇", "杨集镇", "杨集镇", "黄埠镇", "朱里镇", "洙湖镇", "党店镇", "华陂镇", "杨屯乡", "小岳寺乡", "大路李乡", "无量寺乡", "塔桥乡", "西洪乡", "邵店乡", "五龙乡", "东洪乡", "芦岗乡", "齐海乡", "韩寨乡", "崇礼乡", "和店乡"),
            "驻马店市遂平县"  => array("灈阳镇", "车站镇", "车站镇", "玉山镇；阳丰乡", "花庄乡", "槐树乡", "沈寨乡", "和兴乡", "文城乡", "褚堂乡", "石寨铺乡", "嵖岈山乡", "常庄乡"),
            "驻马店市西平县"  => array("五沟营镇", "权寨镇", "权寨镇", "师灵镇", "出山镇", "人和乡", "盆尧乡", "重渠乡", "宋集乡", "焦庄乡", "二郎乡", "谭店乡", "专探乡", "杨庄乡", "吕店乡", "芦庙乡", "蔡寨回族乡"),
            "驻马店市新蔡县"  => array("古吕镇", "棠村镇", "棠村镇", "练村镇", "李桥回族镇", "佛阁寺镇", "龙口镇", "砖店镇", "韩集镇", "陈店镇", "余店乡", "弥陀寺乡", "黄楼乡", "河坞乡", "栎城乡", "涧头乡", "宋岗乡", "顿岗乡", "关津乡", "孙召乡", "化庄乡"),
            "驻马店市正阳县"  => array("真阳镇", "寒冻镇", "寒冻镇", "汝南埠镇", "铜钟镇", "陡沟镇", "熊寨镇", "傅寨乡", "兰青乡", "彭桥乡", "皮店乡", "新阮店乡", "袁寨乡", "油坊店乡", "雷寨乡", "王勿桥乡", "闾河乡", "永兴乡", "大林乡")
        );
        return $city;
    }

    public function city1()
    {
        $city  = $this->city();
        $city1 = array();
        foreach ($city as $key => $val) {
            foreach ($val as $k => $v) {
                $city1[] = $v;
            }
        }
        return $city1;
    }

    public function country()
    {
        $country = array(
            "王楼村", "魏庄", "马庄村", "张庄", "前李楼", "泥土店村", "王岗庄", "白水村", "陈老家村", "刘化王", "孔营",
            "夏楼", "褚庄", "陈庄", "马庄村", "西王营", "曾庄村", "大史庄村", "刘集村", "小何楼", "马营", "刘老家村", "欧庄",
            "大王寨村", "李小桥", "叶庄", "于楼", "中沟村", "李庄", "徐庄", "赵庄", "朱庄", "小李庄", "大婿庄", "洪山庙村",
            "冯桥庄", "焦庄", "孔楼村", "大康庄村", "三官庙", "黄寨", "曹庄", "长营", "大宋庄村", "岳庄",
            "牛堂村", "宋营村", "七里河村", "刘楼村", "高庄", "黄庄", "牛庄", "徐集", "湾张庄村",
            "王子木", "张庄", "王楼", "姚路村", "大勒楼村", "常楼村", "吴庄", "杨集村", "王岗庄",
            "李埠口", "玉皇庙", "屈庄", "三所楼", "王潭村", "周庄村", "葛楼", "后张坡村 ", "郭集", "童庄", "落桥", "王庄寨", "爬头河村", "张大庄", "王老村", "王胡同",
            "赵堂村", "北王张村", "王寨村", "宋洼", "沈庄", "梁堂", "吕庄", "芦庄", "青谷堆寺", "付庄村", "梁庄", "尚庄",
            "大周庄", "丁庄", "丘庄", "火神庙", "廖庄", "崔庄村", "朱楼村", "小杨庄", "大时村"
        );
        return $country;
    }

    public function test()
    {
////        $fieldLength=C('FIELDS_LENGTH');
////        echo $fieldLength['purchaseid'];
////        exit;
//        //$sql="select seq_aid.nextval from dual";
////        $sql="insert into abc values (lpad(seq_aid.nextval,6,'0'),'Marry')";
//        $model=new model();
////        $r=$model->execute($sql);
//
//        $sql0="select seq_aid.nextval from dual";
//        $a=$model->query($sql0);
//        print_r($a);

//        S('test','memcache');
//        $test = S('test');
//        echo $test;

        $memcache = new \Think\Cache\Driver\Memcache();

        $start = microtime(true);
        $str   = str_repeat("a", 1024);//1k数据
        for ($i = 0; $i < 100000; $i++) {
            // $mem->add("bbs_password_",$str,0,0);//入队，bbs为队列名称，password为密码
            $memcache->set("test" . $i, $str, 0, 0);//写入队列
        }
        echo microtime(true) - $start;
    }

    public function test1()
    {
        set_time_limit(0);
        $model = new Model();
        $where = array(
            'c.cardkind'     => array('not in', array('6882', '6666', '6880')),
            'cpl1.amount'    => array('gt', 1000),
            'cu.namechinese' => array('EXP', 'is  null')
        );
//        $field="distinct(cu.customid),cu.namechinese,cu.linktel,cu.personid,";
//        $field.="cu.personidtype,cu.residaddress,cu.birthday,cu.sex,";
//        $field.= "cu.career,cu.personidissuedate,cu.personidexdate";
        $field = "customid,namechinese,linktel,personid,personidtype,residaddress,birthday,sex,career,personidissuedate,personidexdate";
//        $list=$model->table('custom_purchase_logs')->alias('cpl')
//            ->join('card_purchase_logs cpl1 on cpl1.purchaseid=cpl.purchaseid')
//            ->join('cards c on c.cardno=cpl1.cardno')->join('customs_c cc on cc.cid=c.customid')
//            ->join('customs cu on cu.customid=cc.customid')->where($where)->field($field)->select();
        $list = $model->table('customs')->where(array('countrycode' => '房掌柜会员'))->field($field)->select();
        //echo count($list);exit;
        $str     = '';
        $strlist = "会员编号,姓名,电话,身份证号,证件类型,地址,生日,性别,身份证起始有效期,身份证截止有效期\n";
        $strlist = iconv('utf-8', 'gbk', $strlist);
        foreach ($list as $key => $val) {
            $val['namechinese']  = iconv('utf-8', 'gbk', $val['namechinese']);
            $val['personidtype'] = iconv('utf-8', 'gbk', $val['personidtype']);
            $val['residaddress'] = iconv('utf-8', 'gbk', $val['residaddress']);
            $val['sex']          = iconv('utf-8', 'gbk', $val['sex']);
            $strlist             .= $val['customid'] . "\t" . ',' . $val['namechinese'] . ',' . $val['linktel'] . "\t" . ',' . $val['personid'] . "\t" . ',';
            $strlist             .= $val['personidtype'] . ',' . $val['residaddress'] . ',' . $val['birthday'] . ',' . $val['sex'] . ',' . $val['personidissuedate'] . ',' . $val['personidexdate'] . "\n";
        }
        $filename = '会员报表' . date('YmdHis');
        $filename = iconv("utf-8", "gbk", $filename);
        $this->load_csv($strlist, $filename);
    }

    public function test2()
    {
        set_time_limit(0);
        $model     = new Model();
        $array     = array();
        $pro       = array('河南郑州', '河南许昌', '河南周口', '河南漯河', '河南南阳', '河南洛阳', '河南开封',
            '河南信阳', '河南驻马店', '河南三门峡', '河南商丘', '河南焦作', '河南济源', '河南濮阳', '河南鹤壁');
        $careerArr = array('教师', '私营业主', '公司职员', '医生', '护士', '技术工人', '律师', '司机', '企业高管', '公务员');
        $field     = "customid,namechinese,linktel,personid,personidtype,residaddress,birthday,sex,career,personidissuedate,personidexdate";
        $customs   = M('customs');
        $customL   = M('custom_edit_logs');
        foreach ($array as $key => $val) {

            $data1['namechinese'] = $data['namechinese'] = $val['namechinese'];
            $data1['personid']    = $data['personid'] = $val['personid'];
            $data1['sex']         = $data['sex'] = substr($data['personid'], -2, 1) % 2 == 0 ? '女' : '男';
            $data1['birthday']    = $data['birthday'] = substr($val['personid'], 6, 8);
            if (!empty($val['frontimg'])) $data['frontimg'] = '/IMAGES' . $val['frontimg'];
            if (!empty($val['reserveimg'])) $data['reserveimg'] = '/IMAGES' . $val['reserveimg'];
            if (!empty($val['residaddress'])) {
                $data1['residaddress'] = $data['residaddress'] = $val['residaddress'];
            }
            $customs->where(array('customid' => $val['customid']))->save($data);
            //echo $model->getLastSql();
            $customL->where(array('customid' => $val['customid']))->save($data1);
        }
//        foreach($array as $key=>$val){
//            $data=array();
//            //$custom=$customs->where(array('customid'=>$val['customid']))->field($field)->find();
//            $randDate=rand(1167580800,1483200000);
//            $personidissuedate=date('Ymd',$randDate);
//            $personidexdate=date('Ymd',$randDate+86400*365*20);
//            $career=$careerArr[array_rand($careerArr,1)];
//            $data=array('sex'=>$val['sex'],'birthday'=>$val['birthday'],
//                'personidtype'=>'身份证','personid'=>$val['personid'],'linktel'=>$val['linktel'],
//                'residaddress'=>$val['residaddress'],'namechinese'=>$val['namechinese']
//                );
////            if(empty($custom['namechinese'])){
////                $data['namechinese']=$val['namechinese'];
////            }
//            $customs->where(array('customid'=>$val['customid']))->save($data);
//            //echo $model->getLastSql();
//            //$sql="update custom_edit_logs set linktel='{$val['linktel']}' where customid='{$val['customid']}'";
//            $sql="insert into custom_edit_logs values('{$val['customid']}','{$val['linktel']}','{$val['personid']}',";
//            $sql.="'{$val['sex']}','{$val['residaddress']}','身份证','{$career}',";
//            $sql.="'{$personidissuedate}','{$personidexdate}','{$val['birthday']}','{$val['namechinese']}')";
//            $model->execute($sql);
//        }
//        foreach($array as $key=>$val){
//            $custom=$customs->where(array('customid'=>$val['customid']))->field($field)->find();
//            $personid=$custom['personid'];
//            //$personid=$val['personid'];
//            $data['personid']=$personid;
//            $c=0;
//            $data=array();
//            if(empty($custom['sex'])){
//                $sex=substr($personid,-2,1)%2==0?'女':'男';
//                $data['sex']=$sex;
//                $c++;
//            }else{
//                $sex='';
//            }
//            if(empty($custom['personidtype'])){
//                $data['personidtype']='身份证';
//                $c++;
//            }else{
//                $personidtype='';
//            }
//            if(empty($custom['residaddress'])){
//                $data['residaddress']=$pro[array_rand($pro,1)];
//                $c++;
//            }else{
//                $residaddress='';
//            }
//            if(empty($custom['birthday'])){
//                $birth=substr($personid,6,8);
//                $data['birthday']=$birth;
//                $c++;
//            }else{
//                $birthday='';
//            }
//            if(empty($custom['career'])){
//                $career=$careerArr[array_rand($careerArr,1)];
//                $data['career']=$career;
//                $c++;
//            }else{
//                $career='';
//            }
//            if(empty($custom['personidissuedate'])){
//                $randDate=rand(1167580800,1483200000);
//                $personidissuedate=date('Ymd',$randDate);
//                $personidexdate=date('Ymd',$randDate+86400*365*20);
//                $data['personidissuedate']=$personidissuedate;
//                $data['personidexdate']=$personidexdate;
//                $c++;
//            }else{
//                $personidissuedate='';
//                $personidexdate='';
//            }
//            //echo $c;exit;
//            if($c>0){
//                $customs->where(array('customid'=>$val['customid']))->save($data);
//                //echo $model->getLastSql();
//                $sql="insert into custom_edit_logs values('{$val['customid']}','','',";
//                $sql.="'{$sex}','{$residaddress}','{$personidtype}','{$career}',";
//                $sql.="'{$personidissuedate}','{$personidexdate}','{$birthday}','')";
//                //echo $sql;exit;
//                $model->execute($sql);
//            }
//        }
    }

    public function load_csv($arrList, $tableName)
    {
        header("Content-type: text/html; charset=gbk");
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $tableName . ".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $arrList;
    }

    public function test3()
    {
        set_time_limit(0);
        $array = array();
        $cards = M('cards');
        foreach ($array as $key => $val) {
            $map  = array('cardno' => $val['cardno']);
            $list = $cards->where($map)->find();
            if ($list['status'] == 'N') {
                echo '0<br/>';
            } else {
                echo '1<br/>';
            }
        }
        exit;
        $custom  = M('customs');
        $customL = M('custom_edit_logs');
        $model   = new model();
        foreach ($array as $key => $val) {
            $map                 = array('frontimg' => '/IMAGES' . $val['frontimg']);
            $customiInfo         = $custom->where($map)->field('customid')->select();
            $data['personid']    = $val['personid'];
            $data['sex']         = substr($val['personid'], -2, 1) % 2 == 0 ? '女' : '男';
            $birth               = substr($val['personid'], 6, 8);
            $data['namechinese'] = $val['namechinese'];
            $data['birthday']    = $birth;
            //$custom->where(array('customid'=>$val['customid']))->save($data);
            foreach ($customiInfo as $k => $v) {
                $custom->where(array('customid' => $v['customid']))->save($data);
                $customL->where(array('customid' => $v['customid']))->save($data);
            }
        }
        exit;
        $where            = array('cel.personid' => array('EXP', 'IS NOT NULL'), 'c.cardno' => array('EXP', 'IS NULL'));
        $where['_string'] = "c.frontimg like '/IMAGES/photoes%'";
        $list             = M('custom_edit_logs')->alias('cel')
            ->join('customs c on c.customid=cel.customid')
            ->field('cel.*')
            ->where($where)->select();
        $sql              = "";
        foreach ($list as $key => $val) {
            $sql .= "update customs set cardno=" . intval($val['customid']) . " where customid='{$val['customid']}';<br/>";
        }
        echo $sql;
    }

    public function pic()
    {
        set_time_limit(0);
        $root = dirname(THINK_PATH);
        $root = str_replace('\\', '/', $root);
        if (!is_dir($root . "/IMAGES/" . date('Ymd'))) {
            mkdir($root . "/IMAGES/" . date('Ymd'));
        }
        $a       = M('a');
        $customs = M('customs');
        $map     = array('personid' => array('EXP', 'IS NOT NULL'), 'frontimg' => array('EXP', 'IS NULL'), 'thirdzip' => array('EXP', 'IS NULL'));
        $list    = $customs->where($map)->field('customid,personid,namechinese')->limit('0,5000')->select();
        //$list=$customs->where(array('customid'=>'00221528'))->field('customid,personid,namechinese')->limit('0,100')->select();
        foreach ($list as $key => $val) {
            if (empty($val['namechinese'])) {
                continue;
            }
            $name     = $val['namechinese'];
            $personid = $val['personid'];
            $customid = $val['customid'];
            $markCode = substr($personid, 0, 6);
            $list     = $a->where(array('code' => $markCode))->find();
            if (empty($name)) {
                $msg = '会员：' . $customid . '姓名为空' . "\n";
                file_put_contents($root . '/IMAGES/log.txt', $msg, FILE_APPEND);
                $sql = "update customs set thirdzip=1  where customid='{$customid}'";
                $customs->execute($sql);
                continue;
            }
            if ($list == false) {
                $msg = '会员：' . $customid . '身份证找不到对应省份' . "\n";
                file_put_contents($root . '/IMAGES/log.txt', $msg, FILE_APPEND);
                $sql = "update customs set thirdzip=2  where customid='{$customid}'";
                $customs->execute($sql);
                continue;
            }
            if ($list['province'] != '河南省') {
                $msg = '会员：' . $customid . '身份证非河南户籍' . "\n";
                file_put_contents($root . '/IMAGES/log.txt', $msg, FILE_APPEND);
                $sql = "update customs set thirdzip=3  where customid='{$customid}'";
                $customs->execute($sql);
                continue;
            }
            $frontImgArr = $this->creatFrontImg($name, $personid, $list['address']);
            $frontImg    = $frontImgArr[0];
            $reverseImg  = $this->creatReverseImg($list['city']);
            $frontImg    = 'http://106.3.45.146/image/' . $frontImg;
            $reverseImg  = 'http://106.3.45.146/image/' . $reverseImg;
            $sql         = "update customs set frontImg='{$frontImg}',reserveImg='{$reverseImg}',thirdzip=4 where customid='{$customid}'";
            if ($customs->execute($sql)) {
                $msg = '会员：' . $customid . '图片更新成功' . ",图片：{$frontImgArr[1]}\n";
                file_put_contents($root . '/IMAGES/log.txt', $msg, FILE_APPEND);
                continue;
            } else {
                $msg = '会员：' . $customid . '图片更新失败' . "\n";
                file_put_contents($root . '/IMAGES/log.txt', $msg, FILE_APPEND);
                continue;
            }
        }
    }

    public function creatFrontImg($name, $personid, $address)
    {
        $root = dirname(THINK_PATH);
        $root = str_replace('\\', '/', $root);
        $sex  = substr($personid, -2, 1) % 2 == 0 ? '女' : '男';
        $year = substr($personid, 6, 4);
        $mz   = '汉';
        if ($sex == '男') {
            $photoPath = $root . '/IMAGES/man/';
            $rand      = mt_rand(1, 686);
            $photo     = $photoPath . $rand . '.png';
        } else {
            $photoPath = $root . '/IMAGES/women/';
            $rand      = mt_rand(1, 640);
            $photo     = $photoPath . $rand . '.png';
        }
        $month    = intval(substr($personid, 10, 2));
        $day      = intval(substr($personid, 12, 2));
        $im       = imagecreatefrompng($root . "/IMAGES/ly/hb.png");
        $ys       = imagecolorallocate($im, 0, 0, 0);
        $cityName = str_replace('河南省', '', $address);
        if (!empty($this->city[$cityName])) {
            $city = $this->city[$cityName][array_rand($this->city[$cityName], 1)];
        } else {
            $city = $this->city1[array_rand($this->city1, 1)];
        }
        $rand    = mt_rand(1, 30);
        $country = $this->country[array_rand($this->country, 1)] . $rand . '号';
        $dz      = $address . $city . $country;
        ImageTTFText($im, 11, 0, 69, 41, $ys, $root . "/IMAGES/ly/zt.ttf", $name);
        ImageTTFText($im, 10, 0, 69, 66, $ys, $root . "/IMAGES/ly/zt.ttf", $sex);
        ImageTTFText($im, 9, 0, 134, 66, $ys, $root . "/IMAGES/ly/zt.ttf", $mz);
        ImageTTFText($im, 10, 0, 69, 92, $ys, $root . "/IMAGES/ly/zt.ttf", $year);
        if (strlen($month) == 1)
            ImageTTFText($im, 10, 0, 120, 92, $ys, $root . "/IMAGES/ly/zt.ttf", $month);
        else
            ImageTTFText($im, 10, 0, 116, 92, $ys, $root . "/IMAGES/ly/zt.ttf", $month);
        if (strlen($day) == 1)
            ImageTTFText($im, 10, 0, 151, 92, $ys, $root . "/IMAGES/ly/zt.ttf", $day);
        else
            ImageTTFText($im, 10, 0, 147, 92, $ys, $root . "/IMAGES/ly/zt.ttf", $day);
        if (strlen($dz) <= 33) {
            ImageTTFText($im, 9, 0, 69, 121, $ys, $root . "/IMAGES/ly/zt.ttf", $dz);
        } else if (strlen($dz) > 33 && strlen($dz) <= 66) {
            $dz = str_split($dz, 33);
            ImageTTFText($im, 9, 0, 69, 121, $ys, $root . "/IMAGES/ly/zt.ttf", $dz[0]);
            ImageTTFText($im, 9, 0, 69, 140, $ys, $root . "/IMAGES/ly/zt.ttf", $dz[1]);
        }
        ImageTTFText($im, 12, 0, 120, 188, $ys, $root . "/IMAGES/ly/zt.ttf", $personid);
        $strim      = imagecreatefrompng($photo);
        $size       = getimagesize($photo);
        $imgageName = uniqid(time()) . ".png";
        $frontImg   = $root . "/IMAGES/" . date('Ymd') . '/' . $imgageName;
        imagecopy($im, $strim, 220, 25, 0, 0, $size[0], $size[1]);
        imagedestroy($strim);
        imagepng($im, $frontImg);
        imagedestroy($im);
        $imgArr = array($imgageName, $photo);
        return $imgArr;
    }

    public function creatReverseImg($city)
    {
        $root              = dirname(THINK_PATH);
        $root              = str_replace('\\', '/', $root);
        $city              = $city . '公安局';
        $randDate          = rand(1167580800, 1483200000);
        $personidissuedate = date('Y.m.d', $randDate);
        $personidexdate    = date('Y.m.d', strtotime('+20 year', $randDate));
        $string            = $personidissuedate . '-' . $personidexdate;
        $imgageName        = uniqid(time()) . ".png";
        $reverseImg        = $root . "/IMAGES/" . date('Ymd') . '/' . $imgageName;
        $im                = imagecreatefrompng($root . "/IMAGES/ly/fm.png");
        $ys                = imagecolorallocate($im, 0, 0, 0);
        ImageTTFText($im, 10, 0, 147, 162, $ys, $root . "/IMAGES/ly/zt.ttf", $city);
        ImageTTFText($im, 10, 0, 147, 190, $ys, $root . "/IMAGES/ly/zt.ttf", $string);
        imagepng($im, $reverseImg);
        imagedestroy($im);
        return $imgageName;
    }

    public function aaa()
    {

    }

    public function wy()
    {
        $array = array(//            array("eorderid"=>"s2103e9dc90133353f4466fde2fcf6b3","amount"=>"2812.8","panterid"=>"00000374"),
        );
        $trade = M('trade_wastebooks');
        $model = new Model();
        foreach ($array as $key => $val) {
            $eorderid    = trim($val['eorderid']);
            $tradeInfo   = $trade->where(array('eorderid' => array('like', '%' . $eorderid . '%')))->select();
            $tradeAmount = $trade->where(array('eorderid' => array('like', '%' . $eorderid . '%')))->sum('tradepoint');
            $panterid    = trim($val['panterid']);
            //echo $val['amount'].'--'.$tradeAmount;exit;
            if ($val['amount'] != $tradeAmount) {
                echo $val['eorderid'] . '金额与系统金额不符<br/>';
                continue;
            }
            if (count($tradeInfo) == 0) {
                echo $val['eorderid'] . '查无此订单<br/>';
                continue;
            }
            if (count($tradeInfo) == 1) {
                $tradeInfo = $tradeInfo[0];
                //echo $tradeInfo['tradepoint'].'--'.$tradeAmount;exit;
                if ($tradeInfo['tradepoint'] != $tradeAmount) {
                    echo $val['eorderid'] . '金额与系统金额不符<br/>';
                    continue;
                }
                $sql   = "update trade_wastebooks set termno='00000001',termposno='00000001',panterid='{$panterid}' where tradeid='{$tradeInfo['tradeid']}'";
                $tbSql = "update coin_consume set panterid='{$panterid}' where tradeid='{$tradeInfo['tradeid']}'";
                $model->execute($sql);
                $model->execute($tbSql);
            } else {
                foreach ($tradeInfo as $k => $v) {
                    $sql   = "update trade_wastebooks set termno='00000001',termposno='00000001',panterid='{$panterid}' where tradeid='{$v['tradeid']}'";
                    $tbSql = "update coin_consume set panterid='{$panterid}' where tradeid='{$v['tradeid']}'";
                    $model->execute($sql);
                    $model->execute($tbSql);
                }
            }
        }
    }

    public function bbb()
    {
        $array = array();
        $model = new model();
        foreach ($array as $key => $val) {
            $card     = $model->table('cards')->alias('c')->join('customs_c cc on cc.cid=c.customid')
                ->join('customs cu on cu.customid=cc.customid')->where(array('c.cardno' => $val['cardno']))
                ->field('cu.customid,cu.linktel,cu.personid,cu.namechinese')->find();
            $openCard = $model->table('card_purchase_logs')->alias('cpl')
                ->join('custom_purchase_logs cpl1 on cpl1.purchaseid=cpl.purchaseid')
                ->join('customs cu on cu.customid=cpl1.customid')
                ->where(array('cpl.cardno' => $val['cardno'], 'cpl.amount' => 0, 'cpl.point' => 0, 'cpl.description' => '后台充值'))
                ->field('cu.customid,cu.linktel,cu.personid,cu.namechinese')
                ->find();
            //echo $card['customid'].'--'.$card['linktel'].'--'.$openCard['customid'].'--'.$openCard['linktel'].'--'.$openCard['namechinese'].'--'.$openCard['personid'].'<br/>';
            if ($openCard['linktel'] != $card['linktel']) {
                $sql = "update customs set personidtype='身份证',personid='{$openCard['personid']}',namechinese='{$openCard['namechinese']}' where customid='{$card['customid']}';<br/>";
            }
            echo $sql;
        }
    }

    public function mm()
    {
        //echo session_id();exit;
        $array = array();
        $model = new model();
        foreach ($array as $key => $val) {
            $card = $model->table('customs')->alias('cu')
                ->join('customs_c cc on cc.customid=cu.customid')
                ->join('cards c on c.customid=cc.cid')
                ->where(array('cu.customid' => $val['customid'], 'c.cardkind' => '6882'))
                ->field('c.cardno,c.panterid')->find();
            $sql  .= "update customs set panterid='{$card['panterid']}' where customid='{$val['customid']}';<br/>";
        }
        echo $sql;
    }

    public function nn()
    {
        $array = array();
        foreach ($array as $key => $val) {
            $a         = json_decode($val['k'], 1);
            $homearray = explode('-', $a['info']['homeinfo']);
            if (count($homearray) == 6) {
                $building = $homearray[3];//楼栋号
                $unint    = $homearray[4];//单元号
                $housenum = $homearray[5];//房间号
            } elseif (count($homearray) == 5) {
                $building = $homearray[2];//楼栋号
                $unint    = $homearray[3];//单元号
                $housenum = $homearray[4];//房间号
            } elseif (count($homearray) == 4) {
                $building = $homearray[2];//楼栋号
                $unint    = '';//单元号
                $housenum = $homearray[3];//房间号
            } elseif (count($homearray) == 3) {
                $building = $homearray[1];
                $unint    = '';
                $housenum = $homearray[2];
            } else {
                $building = '';//楼栋号
                $unint    = '';//单元号
                $housenum = '';//房间号
            }
            echo $a['info']['orgid'] . '^' . $building . '^' . $unint . '^' . $housenum . '^' . $a['info']['mobilephone'] . '^' . $a['info']['uname'] . '^' . $a['info']['idcardno'] . '<br/>';
        }
    }

    public function lll()
    {
        $field = "a.amount,c.cardno,c.customid";
        $list  = M('account')->alias('a')->join('cards c on c.customid=a.customid')
            ->where(array('a.quanid' => '00000017', 'a.amount' => array('gt', 0), 'a.type' => '02', 'c.panterid' => '00000447'))
            ->field('a.amount,c.cardno,c.customid')
            ->limit(0, 1000)
            ->select();
        //echo M('account')->getLastSql();exit;
        //print_r($list);exit;
        $model = new model();
        foreach ($list as $key => $val) {
            $placeddate = date('Ymd', time());
            $placedtime = date('H:i:s', time());
            $tradeid    = substr($val['cardno'], 15, 4) . date('YmdHis', time());

            $tradeSql = "insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid,tradememo)";
            $tradeSql .= "values('00000000','00000000','00000447','{$tradeid}','{$placeddate}',";
            $tradeSql .= "'{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','02','abcdefgh','0','00000017','君邻会酒店住宿劵清理')";

            $accountSql = "update account set amount=amount-{$val['amount']} where customid='{$val['customid']}' and type='02' and quanid='00000017'";
            echo $tradeSql . '<br/>';
            echo $accountSql . '<br/>';
            $model->startTrans();
            if ($model->execute($tradeSql) && $model->execute($accountSql)) {
                //$model->rollback();
                $model->commit();
                echo $val['cardno'] . '酒店劵清理成功<br/>';
            } else {
                $model->rollback();
                echo $val['cardno'] . '酒店劵清理成功<br/>';
            }
        }
    }

    public function zzz()
    {
        set_time_limit(0);
//        $map=array(
//            'a.quanid'=>array('in',array('00000014','00000015','00000016')),
//            'a.amount'=>array('gt',0),
//            'a.type'=>'02',
//            'c.panterid'=>'00000447'
//        );
        $map   = array(
            'qa.quanid'    => array('in', array('00000014', '00000016')),
            'qa.accountid' => array('egt', '0000000058'),
        );
        $field = "qa.amount,c.cardno,c.customid,qa.quanid";
//        $list=M('account')->alias('a')->join('cards c on c.customid=a.customid')
//            ->where($map)->field($field)
//            ->select();
        $list = M('quan_account')->alias('qa')->join('cards c on c.customid=qa.customid')
            ->where($map)->field($field)->order('qa.accountid asc')
            ->select();
        //$quancz=M('quancz');
        //$model=new model();
        foreach ($list as $key => $val) {
//            $where=array('customid'=>$val['customid'],'quanid'=>$val['quanid']);
//            $quanlist=$quancz->where($where)->select();
//            if(count($quanlist)>1){
//                echo $val['cardno'].':'.$val['quanid'].'有一条以上充值记录<br/>';
//            }elseif(count($quanlist)<1){
//                echo $val['cardno'].':'.$val['quanid'].'无充值记录<br/>';
//            }else{
//                $quanaccountid=$this->getFieldNextNumber('quanaccountid',8);
//                $quanpurchaseid = $this->getFieldNextNumber('quanpurchaseid',8);
//                $acountsif= $this->model->execute("INSERT INTO quan_account(quanid,customid,amount,startdate,purchaseid,accountid,enddate) VALUES('{$val['quanid']}','{$val['customid']}','{$val['amount']}','20170608','{$quanpurchaseid}','{$quanaccountid}','20180607')");
//                $quanczs=$this->model->execute("insert into quancz values('".$val['quanid']."','".$val['amount']."','".date('Ymd')."','".date('H:i:s')."','','0000000000000080','".$val['customid']."','{$quanpurchaseid}')");

            $placeddate = date('Ymd', time());
            $placedtime = date('H:i:s', time());
            $tradeid    = substr($val['cardno'], 15, 4) . date('YmdHis', time());
            $map        = array('tradeid' => $tradeid);
            $c          = M('trade_wastebooks')->where($map)->count();
            if ($c > 0) {
                sleep(1);
                $tradeid = substr($val['cardno'], 15, 4) . date('YmdHis', time());
            }
            $tradeSql = "insert into trade_wastebooks(termno,termposno,panterid,tradeid,placeddate,";
            $tradeSql .= "tradeamount,tradepoint,customid,cardno,placedtime,tradetype,TAC,flag,quanid,tradememo)";
            $tradeSql .= "values('00000000','00000000','00000447','{$tradeid}','{$placeddate}',";
            $tradeSql .= "'{$val['amount']}','0','{$val['customid']}','{$val['cardno']}','{$placedtime}','02','abcdefgh','0','{$val['quanid']}','君邻会艾米劵转新劵')";
            //echo $tradeSql;
//                $accountSql="update account set amount=amount-{$val['amount']} where customid='{$val['customid']}' and type='02' and quanid='{$val['quanid']}'";

            $this->model->execute($tradeSql);
//            $this->model->execute($accountSql);
//            }
        }
    }

    public function wwww()
    {
        set_time_limit(0);
        $where = array('c.cardfee' => 2, 'cupl.tradeflag' => 0, 'cpl.placeddate' => '20170601');
        $model = new model();
        $list  = $model->table('card_purchase_logs')->alias('cpl')
            ->join('custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
            ->join('cards c on c.cardno=cpl.cardno')
            ->join('customs_c cc on cc.cid=c.customid')
            ->join('customs cu on cu.customid=cc.customid')
            ->field('c.cardno,cu.customid')->where($where)->select();
        //echo $model->getLastSql();exit;
        //print_r($list);
        $de = new YjDes();
        foreach ($list as $key => $val) {
            $tb_info = D("Ehome")->getTbinfo($val['customid']);
            $url     = 'http://o2o.yijiahn.com/jyo2o_web/app/user/tongbao/syncinfo.user';
            if ($tb_info) {
                $appid                 = 'SOON-ZZUN-0001';
                $tb_info['customid']   = encode($val['customid']);
                $tb_info['activetype'] = 2;
                $tb_info['appid']      = $appid;
                $tb_sign               = $de->encrypt($tb_info);
                $tb_data               = json_encode($tb_info, JSON_FORCE_OBJECT);

                $this->recordData($tb_data);
                //exit;

                $return_yj  = $this->curlPost($url, $tb_data, $tb_sign);
                $return_arr = json_decode($return_yj, 1);

                if ($return_arr['code'] == '100') {
                    $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "success");
                } else {
                    $this->recordError(date("H:i:s") . '-' . $tb_data . '-' . $return_yj . "\n\t", "YjTbpost", "failed");
                }
            }
        }
    }

    public function aaaaa()
    {
        set_time_limit(0);
        $array              = array();
        $card_purchase_logs = M('card_purchase_logs');
        foreach ($array as $key => $val) {
            $cardno = $val['cardno'];
            $list   = $card_purchase_logs->where(array('cardno' => $cardno, 'amount' => 0, 'point' => 0))->find();
            echo $list['placeddate'] . '<br/>';
        }
    }

    //------------------------------------------------测试定时任务-------------------------------------
    public function printMessage()
    {
        $contents = file_get_contents('test.txt');
        var_dump($contents);

    }

    //定时执行的方法
    public function crons()
    {
        //在文件中写入内容
        file_put_contents('test.txt', date('Y-m-d H:i:s') . '执行定时任务!' . "\r\n<br/>", FILE_APPEND);
    }

    public function fffff()
    {
        set_time_limit(0);
        $array = array();
        $cards = M('cards');
        foreach ($array as $key => $val) {
            $cardArr = explode(',', $val['cardno']);
            foreach ($cardArr as $k => $v) {
                $cardno = $v;
                $card   = $cards->where(array('cardno' => $cardno))->field('cardfee')->find();
                echo $card['cardfee'] . ',';
            }
            echo '<br/>';
        }
    }

    public function test102()
    {
        set_time_limit(0);
        $array = array(
            array("cardno" => "6889374888800004247"),
            array("cardno" => "6889374888800004518"),
            array("cardno" => "6889374888800004259"),
            array("cardno" => "6889374888800004422"),
            array("cardno" => "6889374888800004507"),
            array("cardno" => "6889374888800004322"),
            array("cardno" => "6889374888800004228"),
            array("cardno" => "6889374888800004362"),
            array("cardno" => "6889374888800004430"),
            array("cardno" => "6889374888800004431"),
            array("cardno" => "6889374888800004287"),
            array("cardno" => "6889374888800004288"),
            array("cardno" => "6889374888800004289"),
            array("cardno" => "6889374888800004290"),
            array("cardno" => "6889374888800004291"),
            array("cardno" => "6889374888800004292"),
            array("cardno" => "6889374888800004293"),
            array("cardno" => "6889374888800004294"),
            array("cardno" => "6889374888800004295"),
            array("cardno" => "6889374888800004296"),
            array("cardno" => "6889374888800004297"),
            array("cardno" => "6889374888800004298"),
            array("cardno" => "6889374888800004299"),
            array("cardno" => "6889374888800004300"),
            array("cardno" => "6889374888800004301"),
            array("cardno" => "6889374888800004302"),
            array("cardno" => "6889374888800004303"),
            array("cardno" => "6889374888800004304"),
            array("cardno" => "6889374888800004305"),
            array("cardno" => "6889374888800004306"),
            array("cardno" => "6889374888800004432"),
            array("cardno" => "6889374888800004433"),
            array("cardno" => "6889374888800004434"),
            array("cardno" => "6889374888800004435"),
            array("cardno" => "6889374888800004436"),
            array("cardno" => "6889374888800004437"),
            array("cardno" => "6889374888800004438"),
            array("cardno" => "6889374888800004439"),
            array("cardno" => "6889374888800004440"),
            array("cardno" => "6889374888800004441"),
            array("cardno" => "6889374888800004452"),
            array("cardno" => "6889374888800004453"),
            array("cardno" => "6889374888800004454"),
            array("cardno" => "6889374888800004455"),
            array("cardno" => "6889374888800004456"),
            array("cardno" => "6889374888800004457"),
            array("cardno" => "6889374888800004458"),
            array("cardno" => "6889374888800004459"),
            array("cardno" => "6889374888800004460"),
            array("cardno" => "6889374888800004461"),
            array("cardno" => "6889374888800004462"),
            array("cardno" => "6889374888800004463"),
            array("cardno" => "6889374888800004464"),
            array("cardno" => "6889374888800004465"),
            array("cardno" => "6889374888800004466"),
            array("cardno" => "6889374888800004467"),
            array("cardno" => "6889374888800004468"),
            array("cardno" => "6889374888800004469"),
            array("cardno" => "6889374888800004470"),
            array("cardno" => "6889374888800004492"),
            array("cardno" => "6889374888800004493"),
            array("cardno" => "6889374888800004494"),
            array("cardno" => "6889374888800004495"),
            array("cardno" => "6889374888800004496"),
            array("cardno" => "6889374888800004497"),
            array("cardno" => "6889374888800004498"),
            array("cardno" => "6889374888800004499"),
            array("cardno" => "6889374888800004500"),
            array("cardno" => "6889374888800004501"),
            array("cardno" => "6889374888800004502"),
            array("cardno" => "6889374888800004503"),
            array("cardno" => "6889374888800004504"),
            array("cardno" => "6889374888800004505"),
            array("cardno" => "6889374888800004506"),
            array("cardno" => "6889374888800004508"),
            array("cardno" => "6889374888800004509"),
            array("cardno" => "6889374888800004510"),
            array("cardno" => "6889374888800004511"),
            array("cardno" => "6889374888800004512"),
            array("cardno" => "6889374888800004513"),
            array("cardno" => "6889374888800004514"),
            array("cardno" => "6889374888800004515"),
            array("cardno" => "6889374888800004516"),
            array("cardno" => "6889374888800004517"),
            array("cardno" => "6889374888800004519"),
            array("cardno" => "6889374888800004520"),
            array("cardno" => "6889374888800004521"),
            array("cardno" => "6889374888800004522"),
            array("cardno" => "6889374888800004523"),
            array("cardno" => "6889374888800004524"),
            array("cardno" => "6889374888800004525"),
            array("cardno" => "6889374888800004526"),
            array("cardno" => "6889374888800004527"),
            array("cardno" => "6889374888800004528"),
            array("cardno" => "6889374888800004529"),
            array("cardno" => "6889374888800004530"),
            array("cardno" => "6889374888800004531"),
            array("cardno" => "6889374888800004532"),
            array("cardno" => "6889374888800004307"),
            array("cardno" => "6889374888800004308"),
            array("cardno" => "6889374888800004309"),
            array("cardno" => "6889374888800004310"),
            array("cardno" => "6889374888800004311"),
            array("cardno" => "6889374888800004312"),
            array("cardno" => "6889374888800004313"),
            array("cardno" => "6889374888800004314"),
            array("cardno" => "6889374888800004315"),
            array("cardno" => "6889374888800004316"),
            array("cardno" => "6889374888800004317"),
            array("cardno" => "6889374888800004318"),
            array("cardno" => "6889374888800004319"),
            array("cardno" => "6889374888800004320"),
            array("cardno" => "6889374888800004321"),
            array("cardno" => "6889374888800004323"),
            array("cardno" => "6889374888800004324"),
            array("cardno" => "6889374888800004325"),
            array("cardno" => "6889374888800004326"),
            array("cardno" => "6889374888800004327"),
            array("cardno" => "6889374888800004328"),
            array("cardno" => "6889374888800004329"),
            array("cardno" => "6889374888800004330"),
            array("cardno" => "6889374888800004331"),
            array("cardno" => "6889374888800004332"),
            array("cardno" => "6889374888800004333"),
            array("cardno" => "6889374888800004334"),
            array("cardno" => "6889374888800004335"),
            array("cardno" => "6889374888800004336"),
            array("cardno" => "6889374888800004337"),
            array("cardno" => "6889374888800004338"),
            array("cardno" => "6889374888800004339"),
            array("cardno" => "6889374888800004340"),
            array("cardno" => "6889374888800004341"),
            array("cardno" => "6889374888800004342"),
            array("cardno" => "6889374888800004343"),
            array("cardno" => "6889374888800004344"),
            array("cardno" => "6889374888800004345"),
            array("cardno" => "6889374888800004346"),
            array("cardno" => "6889374888800004347"),
            array("cardno" => "6889374888800004533"),
            array("cardno" => "6889374888800004534"),
            array("cardno" => "6889374888800004535"),
            array("cardno" => "6889374888800004536"),
            array("cardno" => "6889374888800004537"),
            array("cardno" => "6889374888800004538"),
            array("cardno" => "6889374888800004539"),
            array("cardno" => "6889374888800004540"),
            array("cardno" => "6889374888800004541"),
            array("cardno" => "6889374888800004542"),
            array("cardno" => "6889374888800004190"),
            array("cardno" => "6889374888800004191"),
            array("cardno" => "6889374888800004192"),
            array("cardno" => "6889374888800004193"),
            array("cardno" => "6889374888800004194"),
            array("cardno" => "6889374888800004195"),
            array("cardno" => "6889374888800004196"),
            array("cardno" => "6889374888800004197"),
            array("cardno" => "6889374888800004198"),
            array("cardno" => "6889374888800004199"),
            array("cardno" => "6889374888800004200"),
            array("cardno" => "6889374888800004201"),
            array("cardno" => "6889374888800004202"),
            array("cardno" => "6889374888800004203"),
            array("cardno" => "6889374888800004204"),
            array("cardno" => "6889374888800004205"),
            array("cardno" => "6889374888800004206"),
            array("cardno" => "6889374888800004207"),
            array("cardno" => "6889374888800004208"),
            array("cardno" => "6889374888800004209"),
            array("cardno" => "6889374888800004210"),
            array("cardno" => "6889374888800004211"),
            array("cardno" => "6889374888800004212"),
            array("cardno" => "6889374888800004213"),
            array("cardno" => "6889374888800004214"),
            array("cardno" => "6889374888800004215"),
            array("cardno" => "6889374888800004216"),
            array("cardno" => "6889374888800004217"),
            array("cardno" => "6889374888800004218"),
            array("cardno" => "6889374888800004219"),
            array("cardno" => "6889374888800004220"),
            array("cardno" => "6889374888800004221"),
            array("cardno" => "6889374888800004222"),
            array("cardno" => "6889374888800004223"),
            array("cardno" => "6889374888800004224"),
            array("cardno" => "6889374888800004348"),
            array("cardno" => "6889374888800004349"),
            array("cardno" => "6889374888800004350"),
            array("cardno" => "6889374888800004351"),
            array("cardno" => "6889374888800004352"),
            array("cardno" => "6889374888800004353"),
            array("cardno" => "6889374888800004354"),
            array("cardno" => "6889374888800004355"),
            array("cardno" => "6889374888800004356"),
            array("cardno" => "6889374888800004357"),
            array("cardno" => "6889374888800004358"),
            array("cardno" => "6889374888800004359"),
            array("cardno" => "6889374888800004360"),
            array("cardno" => "6889374888800004361"),
            array("cardno" => "6889374888800004363"),
            array("cardno" => "6889374888800004364"),
            array("cardno" => "6889374888800004365"),
            array("cardno" => "6889374888800004366"),
            array("cardno" => "6889374888800004367"),
            array("cardno" => "6889374888800004368"),
            array("cardno" => "6889374888800004369"),
            array("cardno" => "6889374888800004370"),
            array("cardno" => "6889374888800004371"),
            array("cardno" => "6889374888800004372"),
            array("cardno" => "6889374888800004373"),
            array("cardno" => "6889374888800004374"),
            array("cardno" => "6889374888800004375"),
            array("cardno" => "6889374888800004376"),
            array("cardno" => "6889374888800004377"),
            array("cardno" => "6889374888800004378"),
            array("cardno" => "6889374888800004379"),
            array("cardno" => "6889374888800004380"),
            array("cardno" => "6889374888800004381"),
            array("cardno" => "6889374888800004382"),
            array("cardno" => "6889374888800004383"),
            array("cardno" => "6889374888800004384"),
            array("cardno" => "6889374888800004385"),
            array("cardno" => "6889374888800004386"),
            array("cardno" => "6889374888800004387"),
            array("cardno" => "6889374888800004388"),
            array("cardno" => "6889374888800004225"),
            array("cardno" => "6889374888800004226"),
            array("cardno" => "6889374888800004227"),
            array("cardno" => "6889374888800004229"),
            array("cardno" => "6889374888800004230"),
            array("cardno" => "6889374888800004246"),
            array("cardno" => "6889374888800004248"),
            array("cardno" => "6889374888800004249"),
            array("cardno" => "6889374888800004250"),
            array("cardno" => "6889374888800004251"),
            array("cardno" => "6889374888800004252"),
            array("cardno" => "6889374888800004253"),
            array("cardno" => "6889374888800004254"),
            array("cardno" => "6889374888800004255"),
            array("cardno" => "6889374888800004256"),
            array("cardno" => "6889374888800004257"),
            array("cardno" => "6889374888800004258"),
            array("cardno" => "6889374888800004260"),
            array("cardno" => "6889374888800004261"),
            array("cardno" => "6889374888800004262"),
            array("cardno" => "6889374888800004263"),
            array("cardno" => "6889374888800004264"),
            array("cardno" => "6889374888800004265"),
            array("cardno" => "6889374888800004389"),
            array("cardno" => "6889374888800004390"),
            array("cardno" => "6889374888800004391"),
            array("cardno" => "6889374888800004392"),
            array("cardno" => "6889374888800004393"),
            array("cardno" => "6889374888800004394"),
            array("cardno" => "6889374888800004395"),
            array("cardno" => "6889374888800004396"),
            array("cardno" => "6889374888800004397"),
            array("cardno" => "6889374888800004398"),
            array("cardno" => "6889374888800004399"),
            array("cardno" => "6889374888800004400"),
            array("cardno" => "6889374888800004401"),
            array("cardno" => "6889374888800004402"),
            array("cardno" => "6889374888800004403"),
            array("cardno" => "6889374888800004404"),
            array("cardno" => "6889374888800004405"),
            array("cardno" => "6889374888800004406"),
            array("cardno" => "6889374888800004407"),
            array("cardno" => "6889374888800004408"),
            array("cardno" => "6889374888800004409"),
            array("cardno" => "6889374888800004410"),
            array("cardno" => "6889374888800004411"),
            array("cardno" => "6889374888800004412"),
            array("cardno" => "6889374888800004413"),
            array("cardno" => "6889374888800004414"),
            array("cardno" => "6889374888800004415"),
            array("cardno" => "6889374888800004416"),
            array("cardno" => "6889374888800004417"),
            array("cardno" => "6889374888800004418"),
            array("cardno" => "6889374888800004419"),
            array("cardno" => "6889374888800004420"),
            array("cardno" => "6889374888800004421"),

        );
        $model = new model();
        foreach ($array as $key => $val) {
            $cardno = $val['cardno'];
//            $list=$model->table('fangzg_c')->alias('fc')->join('fangzg f on f.tradeid=fc.tradeid')
//                ->field('f.tradeid,f.name,f.amount,f.consumedmoney')->where(array('fc.cardno'=>$cardno))->find();
            $list    = $model->table('card_purchase_logs')->where(array('cardno' => $cardno))->find();
            $list1   = $model->table('custom_purchase_logs')->where(array('purchaseid' => $list['purchaseid']))->find();
            $account = $model->table('cards')->alias('c')->join('account a on a.customid=c.customid')->where(array('cardno' => $cardno))->select();
            $fc      = $model->table('fangzg_c')->where(array('cardno' => $cardno))->find();
            echo json_encode($list) . '<br/>';
            echo json_encode($list1) . '<br/>';
            echo json_encode($account) . '<br/>';
            echo json_encode($fc) . '<br/>';
        }
    }

    public function updateCard()
    {
        $str = "UPDATE cards SET status='Y',customid='01183785',cardbalance='0',exdate='20240315' where cardno='2336010788800003434' and customid = 0
UPDATE cards SET status='Y',customid='01183947',cardbalance='0',exdate='20240315' where cardno='2336010788800003435' and customid = 0
UPDATE cards SET status='Y',customid='01183946',cardbalance='0',exdate='20240315' where cardno='2336010788800003436' and customid = 0
UPDATE cards SET status='Y',customid='01183683',cardbalance='0',exdate='20240315' where cardno='2336010788800003437' and customid = 0
UPDATE cards SET status='Y',customid='01184132',cardbalance='0',exdate='20240315' where cardno='2336010788800003438' and customid = 0
UPDATE cards SET status='Y',customid='01183480',cardbalance='0',exdate='20240315' where cardno='2336010788800003439' and customid = 0
UPDATE cards SET status='Y',customid='01183479',cardbalance='0',exdate='20240315' where cardno='2336010788800003440' and customid = 0
UPDATE cards SET status='Y',customid='01183939',cardbalance='0',exdate='20240315' where cardno='2336010788800003441' and customid = 0
UPDATE cards SET status='Y',customid='01184088',cardbalance='0',exdate='20240315' where cardno='2336010788800003442' and customid = 0
UPDATE cards SET status='Y',customid='01184199',cardbalance='0',exdate='20240315' where cardno='2336010788800003443' and customid = 0
UPDATE cards SET status='Y',customid='01181732',cardbalance='0',exdate='20240315' where cardno='2336010788800003444' and customid = 0
UPDATE cards SET status='Y',customid='01183957',cardbalance='0',exdate='20240315' where cardno='2336010788800003445' and customid = 0
UPDATE cards SET status='Y',customid='01183693',cardbalance='0',exdate='20240315' where cardno='2336010788800003446' and customid = 0
UPDATE cards SET status='Y',customid='01184035',cardbalance='0',exdate='20240315' where cardno='2336010788800003447' and customid = 0
UPDATE cards SET status='Y',customid='01181743',cardbalance='0',exdate='20240315' where cardno='2336010788800003448' and customid = 0
UPDATE cards SET status='Y',customid='01183908',cardbalance='0',exdate='20240315' where cardno='2336010788800003449' and customid = 0
UPDATE cards SET status='Y',customid='01184186',cardbalance='0',exdate='20240315' where cardno='2336010788800003450' and customid = 0
UPDATE cards SET status='Y',customid='01183191',cardbalance='0',exdate='20240315' where cardno='2336010788800003451' and customid = 0
UPDATE cards SET status='Y',customid='01184301',cardbalance='0',exdate='20240315' where cardno='2336010788800003452' and customid = 0
UPDATE cards SET status='Y',customid='01184300',cardbalance='0',exdate='20240315' where cardno='2336010788800003453' and customid = 0
UPDATE cards SET status='Y',customid='01182554',cardbalance='0',exdate='20240315' where cardno='2336010788800003454' and customid = 0
UPDATE cards SET status='Y',customid='01182553',cardbalance='0',exdate='20240315' where cardno='2336010788800003911' and customid = 0
UPDATE cards SET status='Y',customid='01184111',cardbalance='0',exdate='20240315' where cardno='2336010788800003912' and customid = 0
UPDATE cards SET status='Y',customid='01183560',cardbalance='0',exdate='20240315' where cardno='2336010788800003913' and customid = 0
UPDATE cards SET status='Y',customid='01184105',cardbalance='0',exdate='20240315' where cardno='2336010788800003914' and customid = 0
UPDATE cards SET status='Y',customid='01183414',cardbalance='0',exdate='20240315' where cardno='2336010788800003915' and customid = 0
UPDATE cards SET status='Y',customid='01183413',cardbalance='0',exdate='20240315' where cardno='2336010788800003916' and customid = 0
UPDATE cards SET status='Y',customid='01183585',cardbalance='0',exdate='20240315' where cardno='2336010788800003917' and customid = 0
UPDATE cards SET status='Y',customid='01183418',cardbalance='0',exdate='20240315' where cardno='2336010788800003918' and customid = 0
UPDATE cards SET status='Y',customid='01183417',cardbalance='0',exdate='20240315' where cardno='2336010788800003919' and customid = 0
UPDATE cards SET status='Y',customid='01183811',cardbalance='0',exdate='20240315' where cardno='2336010788800003920' and customid = 0
UPDATE cards SET status='Y',customid='01183617',cardbalance='0',exdate='20240315' where cardno='2336010788800003921' and customid = 0
UPDATE cards SET status='Y',customid='01184042',cardbalance='0',exdate='20240315' where cardno='2336010788800003922' and customid = 0
UPDATE cards SET status='Y',customid='01184278',cardbalance='0',exdate='20240315' where cardno='2336010788800003923' and customid = 0
UPDATE cards SET status='Y',customid='01184277',cardbalance='0',exdate='20240315' where cardno='2336010788800003924' and customid = 0
UPDATE cards SET status='Y',customid='01183717',cardbalance='0',exdate='20240315' where cardno='2336010788800003925' and customid = 0
UPDATE cards SET status='Y',customid='01183675',cardbalance='0',exdate='20240315' where cardno='2336010788800003926' and customid = 0
UPDATE cards SET status='Y',customid='01183551',cardbalance='0',exdate='20240315' where cardno='2336010788800003927' and customid = 0
UPDATE cards SET status='Y',customid='01183550',cardbalance='0',exdate='20240315' where cardno='2336010788800003928' and customid = 0
UPDATE cards SET status='Y',customid='01181765',cardbalance='0',exdate='20240315' where cardno='2336010788800003929' and customid = 0
UPDATE cards SET status='Y',customid='01183987',cardbalance='0',exdate='20240315' where cardno='2336010788800003930' and customid = 0
UPDATE cards SET status='Y',customid='01183840',cardbalance='0',exdate='20240315' where cardno='2336010788800003931' and customid = 0
UPDATE cards SET status='Y',customid='01183667',cardbalance='0',exdate='20240315' where cardno='2336010788800003932' and customid = 0
UPDATE cards SET status='Y',customid='01183968',cardbalance='0',exdate='20240315' where cardno='2336010788800003933' and customid = 0
UPDATE cards SET status='Y',customid='01183450',cardbalance='0',exdate='20240315' where cardno='2336010788800003934' and customid = 0
UPDATE cards SET status='Y',customid='01184095',cardbalance='0',exdate='20240315' where cardno='2336010788800003935' and customid = 0
UPDATE cards SET status='Y',customid='01183830',cardbalance='0',exdate='20240315' where cardno='2336010788800003936' and customid = 0
UPDATE cards SET status='Y',customid='01183267',cardbalance='0',exdate='20240315' where cardno='2336010788800003937' and customid = 0
UPDATE cards SET status='Y',customid='01184222',cardbalance='0',exdate='20240315' where cardno='2336010788800003938' and customid = 0
UPDATE cards SET status='Y',customid='01184223',cardbalance='0',exdate='20240315' where cardno='2336010788800003939' and customid = 0
UPDATE cards SET status='Y',customid='01184017',cardbalance='0',exdate='20240315' where cardno='2336010788800003940' and customid = 0
UPDATE cards SET status='Y',customid='01181793',cardbalance='0',exdate='20240315' where cardno='2336010788800003941' and customid = 0
UPDATE cards SET status='Y',customid='01184257',cardbalance='0',exdate='20240315' where cardno='2336010788800003942' and customid = 0
UPDATE cards SET status='Y',customid='01184256',cardbalance='0',exdate='20240315' where cardno='2336010788800003943' and customid = 0
UPDATE cards SET status='Y',customid='01184255',cardbalance='0',exdate='20240315' where cardno='2336010788800003944' and customid = 0
UPDATE cards SET status='Y',customid='01183749',cardbalance='0',exdate='20240315' where cardno='2336010788800003945' and customid = 0
UPDATE cards SET status='Y',customid='01184310',cardbalance='0',exdate='20240315' where cardno='2336010788800003946' and customid = 0
UPDATE cards SET status='Y',customid='01184312',cardbalance='0',exdate='20240315' where cardno='2336010788800003947' and customid = 0
UPDATE cards SET status='Y',customid='01183639',cardbalance='0',exdate='20240315' where cardno='2336010788800003948' and customid = 0
UPDATE cards SET status='Y',customid='01184128',cardbalance='0',exdate='20240315' where cardno='2336010788800003949' and customid = 0
UPDATE cards SET status='Y',customid='01183242',cardbalance='0',exdate='20240315' where cardno='2336010788800003950' and customid = 0
UPDATE cards SET status='Y',customid='01183243',cardbalance='0',exdate='20240315' where cardno='2336010788800003951' and customid = 0
UPDATE cards SET status='Y',customid='01183844',cardbalance='0',exdate='20240315' where cardno='2336010788800003455' and customid = 0
UPDATE cards SET status='Y',customid='01183386',cardbalance='0',exdate='20240315' where cardno='2336010788800003456' and customid = 0
UPDATE cards SET status='Y',customid='01183387',cardbalance='0',exdate='20240315' where cardno='2336010788800003457' and customid = 0
UPDATE cards SET status='Y',customid='01183274',cardbalance='0',exdate='20240315' where cardno='2336010788800003458' and customid = 0
UPDATE cards SET status='Y',customid='01184228',cardbalance='0',exdate='20240315' where cardno='2336010788800003459' and customid = 0
UPDATE cards SET status='Y',customid='01184227',cardbalance='0',exdate='20240315' where cardno='2336010788800003460' and customid = 0
UPDATE cards SET status='Y',customid='01183493',cardbalance='0',exdate='20240315' where cardno='2336010788800003461' and customid = 0
UPDATE cards SET status='Y',customid='01183217',cardbalance='0',exdate='20240315' where cardno='2336010788800003462' and customid = 0
UPDATE cards SET status='Y',customid='01183218',cardbalance='0',exdate='20240315' where cardno='2336010788800003463' and customid = 0
UPDATE cards SET status='Y',customid='01184208',cardbalance='0',exdate='20240315' where cardno='2336010788800003464' and customid = 0
UPDATE cards SET status='Y',customid='01184207',cardbalance='0',exdate='20240315' where cardno='2336010788800003465' and customid = 0
UPDATE cards SET status='Y',customid='01184238',cardbalance='0',exdate='20240315' where cardno='2336010788800003466' and customid = 0
UPDATE cards SET status='Y',customid='01184239',cardbalance='0',exdate='20240315' where cardno='2336010788800003467' and customid = 0
UPDATE cards SET status='Y',customid='01184011',cardbalance='0',exdate='20240315' where cardno='2336010788800003468' and customid = 0
UPDATE cards SET status='Y',customid='01184059',cardbalance='0',exdate='20240315' where cardno='2336010788800003469' and customid = 0
UPDATE cards SET status='Y',customid='01181808',cardbalance='0',exdate='20240315' where cardno='2336010788800003470' and customid = 0
UPDATE cards SET status='Y',customid='01183406',cardbalance='0',exdate='20240315' where cardno='2336010788800003471' and customid = 0
";
//        preg_match_all("/.*?customid='(.*?)'.*?cardno='(.*?)'/", $str, $ma);
//        $customid = $ma[1];
//        $cardno   = $ma[2];
//        $cardData = [
//
//        ];
//        foreach ($customid as $k => $v) {
//            $cardData[$k]=[
//                'customid'=>$customid[$k],
//                'cardno'=>$cardno[$k]
//            ];
//        }
//echo  json_encode($cardData,256);exit;

        $json = '[{"customid":"01183785","cardno":"2336010788800003434"},{"customid":"01183947","cardno":"2336010788800003435"},{"customid":"01183946","cardno":"2336010788800003436"},{"customid":"01183683","cardno":"2336010788800003437"},{"customid":"01184132","cardno":"2336010788800003438"},{"customid":"01183480","cardno":"2336010788800003439"},{"customid":"01183479","cardno":"2336010788800003440"},{"customid":"01183939","cardno":"2336010788800003441"},{"customid":"01184088","cardno":"2336010788800003442"},{"customid":"01184199","cardno":"2336010788800003443"},{"customid":"01181732","cardno":"2336010788800003444"},{"customid":"01183957","cardno":"2336010788800003445"},{"customid":"01183693","cardno":"2336010788800003446"},{"customid":"01184035","cardno":"2336010788800003447"},{"customid":"01181743","cardno":"2336010788800003448"},{"customid":"01183908","cardno":"2336010788800003449"},{"customid":"01184186","cardno":"2336010788800003450"},{"customid":"01183191","cardno":"2336010788800003451"},{"customid":"01184301","cardno":"2336010788800003452"},{"customid":"01184300","cardno":"2336010788800003453"},{"customid":"01182554","cardno":"2336010788800003454"},{"customid":"01182553","cardno":"2336010788800003911"},{"customid":"01184111","cardno":"2336010788800003912"},{"customid":"01183560","cardno":"2336010788800003913"},{"customid":"01184105","cardno":"2336010788800003914"},{"customid":"01183414","cardno":"2336010788800003915"},{"customid":"01183413","cardno":"2336010788800003916"},{"customid":"01183585","cardno":"2336010788800003917"},{"customid":"01183418","cardno":"2336010788800003918"},{"customid":"01183417","cardno":"2336010788800003919"},{"customid":"01183811","cardno":"2336010788800003920"},{"customid":"01183617","cardno":"2336010788800003921"},{"customid":"01184042","cardno":"2336010788800003922"},{"customid":"01184278","cardno":"2336010788800003923"},{"customid":"01184277","cardno":"2336010788800003924"},{"customid":"01183717","cardno":"2336010788800003925"},{"customid":"01183675","cardno":"2336010788800003926"},{"customid":"01183551","cardno":"2336010788800003927"},{"customid":"01183550","cardno":"2336010788800003928"},{"customid":"01181765","cardno":"2336010788800003929"},{"customid":"01183987","cardno":"2336010788800003930"},{"customid":"01183840","cardno":"2336010788800003931"},{"customid":"01183667","cardno":"2336010788800003932"},{"customid":"01183968","cardno":"2336010788800003933"},{"customid":"01183450","cardno":"2336010788800003934"},{"customid":"01184095","cardno":"2336010788800003935"},{"customid":"01183830","cardno":"2336010788800003936"},{"customid":"01183267","cardno":"2336010788800003937"},{"customid":"01184222","cardno":"2336010788800003938"},{"customid":"01184223","cardno":"2336010788800003939"},{"customid":"01184017","cardno":"2336010788800003940"},{"customid":"01181793","cardno":"2336010788800003941"},{"customid":"01184257","cardno":"2336010788800003942"},{"customid":"01184256","cardno":"2336010788800003943"},{"customid":"01184255","cardno":"2336010788800003944"},{"customid":"01183749","cardno":"2336010788800003945"},{"customid":"01184310","cardno":"2336010788800003946"},{"customid":"01184312","cardno":"2336010788800003947"},{"customid":"01183639","cardno":"2336010788800003948"},{"customid":"01184128","cardno":"2336010788800003949"},{"customid":"01183242","cardno":"2336010788800003950"},{"customid":"01183243","cardno":"2336010788800003951"},{"customid":"01183844","cardno":"2336010788800003455"},{"customid":"01183386","cardno":"2336010788800003456"},{"customid":"01183387","cardno":"2336010788800003457"},{"customid":"01183274","cardno":"2336010788800003458"},{"customid":"01184228","cardno":"2336010788800003459"},{"customid":"01184227","cardno":"2336010788800003460"},{"customid":"01183493","cardno":"2336010788800003461"},{"customid":"01183217","cardno":"2336010788800003462"},{"customid":"01183218","cardno":"2336010788800003463"},{"customid":"01184208","cardno":"2336010788800003464"},{"customid":"01184207","cardno":"2336010788800003465"},{"customid":"01184238","cardno":"2336010788800003466"},{"customid":"01184239","cardno":"2336010788800003467"},{"customid":"01184011","cardno":"2336010788800003468"},{"customid":"01184059","cardno":"2336010788800003469"},{"customid":"01181808","cardno":"2336010788800003470"},{"customid":"01183406","cardno":"2336010788800003471"}]';
        //重组数据
        $data = json_decode($json, true);
        //查询  购卡/充值单 custom_purchase_logs、 充值表 card_purchase_logs、卡批量激活表 card_active_logs、售卡审核表 audit_logs 重复数据
        // 测试日志表除了卡号其他是否正确
        //        $data = M('card_active_logs')
//            ->alias('acl')
//            ->join('left join card_purchase_logs cpl on cpl.cardno=acl.cardno')
//            ->join('left join custom_purchase_logs cupl on cupl.purchaseid=cpl.purchaseid')
//            ->join('left join audit_logs al on cupl.purchaseid=al.purchaseid')->where(['acl.cardno' => '2336010788800004259', 'cpl.cardno' => '2336010788800004259'])->buildSql();
//      echo $data;
//        exit;
        //经过测试卡除了卡号一样以外其他数据正常
        /**
         * ('2336010788800004259','0000000000001096','20190313',0,'Y','00 ','20190313','11:07:04','售卡激活','01182565','00000219','00000000','000000000002436165','1096000001560799','2336010788800004259',5000,0,'20190313','11:12:04','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01182544','1096000001560799','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','11:07:04','20190313','11:12:04',null,'0000000001455456','1096000001560799','审核通过                ','购卡审核通过','20190313      ','0000000000001096','11:12:04',1);
         * ('2336010788800004259','0000000000000059','20190313',0,'Y','00 ','20190313','11:06:48','售卡激活','01182554','00000219','00000000','000000000002436150','0059000001560815','2336010788800004259',5000,0,'20190313','11:11:48','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01182553','0059000001560815','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','11:06:48','20190313','11:11:48',null,'0000000001455473','0059000001560815','审核通过                ','购卡审核通过','20190313      ','0000000000000059','11:11:48',1);
         *
         * ('2336010788800004258','0000000000000059','20190313',0,'Y','00 ','20190313','11:06:47','售卡激活','01182553','00000219','00000000','000000000002436149','0059000001560774','2336010788800004258',5000,0,'20190313','11:11:47','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01182553','0059000001560774','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','11:06:47','20190313','11:11:47',null,'0000000001455432','0059000001560774','审核通过                ','购卡审核通过','20190313      ','0000000000000059','11:11:47',1);
         * ('2336010788800004258','0000000000001096','20190313',0,'Y','00 ','20190313','11:07:04','售卡激活','01182544','00000219','00000000','000000000002436164','1096000001560798','2336010788800004258',5000,0,'20190313','11:12:04','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01182544','1096000001560798','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','11:07:03','20190313','11:12:03',null,'0000000001455455','1096000001560798','审核通过                ','购卡审核通过','20190313      ','0000000000001096','11:12:04',1);
         *
         *
         * ('2336010788800003910','0000000000001096','20190313',0,'Y','00','20190313','17:03:56','售卡激活','01184262','00000219','00000000','000000000002440043','1096000001562615','2336010788800003910',5000,0,'20190313','17:08:56','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01184262','1096000001562615','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','17:03:56','20190313','17:08:56',null,'0000000001457270','1096000001562615','审核通过                ','购卡审核通过','20190313      ','0000000000001096','17:08:56',1);
         * ('2336010788800003910','0000000000000059','20190313',0,'Y','00','20190313','17:03:50','售卡激活','01184277','00000219','00000000','000000000002440035','0059000001562611','2336010788800003910',5000,0,'20190313','17:08:50','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01184277','0059000001562611','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','17:03:50','20190313','17:08:50',null,'0000000001457251','0059000001562611','审核通过                ','购卡审核通过','20190313      ','0000000000000059','17:08:50',1
         *
         *
         * /
         * /**
         * 测试SQL
         * 第一条
         * update card_active_logs  set cardno='2336010788800003454' where cardno='2336010788800004259' and userid='0000000000000059' and customid='01182554'
         * update card_purchase_logs  set cardno='2336010788800003454' where cardno='2336010788800004259' and userid='0000000000000059'
         * 第二条
         * update card_active_logs  set cardno='2336010788800003911' where cardno='2336010788800004258' and userid='0000000000000059' and customid='01182553'
         * update card_purchase_logs  set cardno='2336010788800003911' where cardno='2336010788800004258' and userid='0000000000000059'
         * 第三条
         * update card_active_logs  set cardno='2336010788800003943' where cardno='2336010788800003910' and userid='0000000000000059' and customid='01184277'
         * update card_purchase_logs  set cardno='2336010788800003943' where cardno='2336010788800003910' and userid='0000000000000059'
         *
         * 结论只要修改 card_purchase_logs  card_active_logs 的卡号，就可以正常
         */
        /**
         *测试SQL 第二次
         * 第一条
         *('2336010788800003909','0000000000000059','20190313',0,'Y','00                  ','20190313','17:01:46','售卡激活','01184257','00000219','00000000','000000000002440012','0059000001562590','2336010788800003909',5000,0,'20190313','17:06:46','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01184255','0059000001562590','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','17:01:46','20190313','17:06:46',null,'0000000001457265','0059000001562590','审核通过                ','购卡审核通过','20190313      ','0000000000000059','17:06:46',1);
         *('2336010788800003909','0000000000001096','20190313',0,'Y','00                  ','20190313','17:02:05','售卡激活','01184272','00000219','00000000','000000000002440025','1096000001562606','2336010788800003909',5000,0,'20190313','17:07:05','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01184270','1096000001562606','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','17:02:05','20190313','17:07:05',null,'0000000001457246','1096000001562606','审核通过                ','购卡审核通过','20190313      ','0000000000001096','17:07:05',1);
         *
         * 第二条
         *('2336010788800003908','0000000000000059','20190313',0,'Y','00                  ','20190313','17:01:45','售卡激活','01184256','00000219','00000000','000000000002440011','0059000001562589','2336010788800003908',5000,0,'20190313','17:06:45','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01184255','0059000001562589','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','17:01:45','20190313','17:06:45',null,'0000000001457264','0059000001562589','审核通过                ','购卡审核通过','20190313      ','0000000000000059','17:06:45',1);
         *('2336010788800003908','0000000000001096','20190313',0,'Y','00                  ','20190313','17:02:04','售卡激活','01184271','00000219','00000000','000000000002440024','1096000001562605','2336010788800003908',5000,0,'20190313','17:07:04','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01184270','1096000001562605','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','17:02:04','20190313','17:07:04',null,'0000000001457245','1096000001562605','审核通过                ','购卡审核通过','20190313      ','0000000000001096','17:07:04',1);
         * 第三条
         *('2336010788800003907','0000000000000059','20190313',0,'Y','00                  ','20190313','17:01:45','售卡激活','01184255','00000219','00000000','000000000002440010','0059000001562588','2336010788800003907',5000,0,'20190313','17:06:45','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01184255','0059000001562588','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','17:01:45','20190313','17:06:45',null,'0000000001457263','0059000001562588','审核通过                ','购卡审核通过','20190313      ','0000000000000059','17:06:45',1);
         *('2336010788800003907','0000000000001096','20190313',0,'Y','00                  ','20190313','17:02:04','售卡激活','01184270','00000219','00000000','000000000002440023','1096000001562604','2336010788800003907',5000,0,'20190313','17:07:04','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01184270','1096000001562604','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','17:02:04','20190313','17:07:04',null,'0000000001457244','1096000001562604','审核通过                ','购卡审核通过','20190313      ','0000000000001096','17:07:04',1);
         * 第四条
         *('2336010788800003905','0000000000000059','20190313',0,'Y','00                  ','20190313','16:59:38','售卡激活','01184223','00000219','00000000','000000000002439973','0059000001562597','2336010788800003905',5000,0,'20190313','17:04:38','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01184222','0059000001562597','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','16:59:38','20190313','17:04:38',null,'0000000001457237','0059000001562597','审核通过                ','购卡审核通过','20190313      ','0000000000000059','17:04:38',1);
         *('2336010788800003905','0000000000001096','20190313',0,'Y','00                  ','20190313','17:00:00','售卡激活','01184248','00000219','00000000','000000000002439993','1096000001562581','2336010788800003905',5000,0,'20190313','17:05:00','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01184247','1096000001562581','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','17:00:00','20190313','17:05:00',null,'0000000001457256','1096000001562581','审核通过                ','购卡审核通过','20190313      ','0000000000001096','17:05:00',1);
         * 第五条
         *('2336010788800003904','0000000000000059','20190313',0,'Y','00                  ','20190313','16:59:37','售卡激活','01184222','00000219','00000000','000000000002439971','0059000001562596','2336010788800003904',5000,0,'20190313','17:04:37','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01184222','0059000001562596','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','16:59:37','20190313','17:04:37',null,'0000000001457236','0059000001562596','审核通过                ','购卡审核通过','20190313      ','0000000000000059','17:04:37',1);
         *('2336010788800003904','0000000000001096','20190313',0,'Y','00                  ','20190313','16:59:59','售卡激活','01184247','00000219','00000000','000000000002439992','1096000001562580','2336010788800003904',5000,0,'20190313','17:04:59','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01184247','1096000001562580','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','16:59:59','20190313','17:04:59',null,'0000000001457255','1096000001562580','审核通过                ','购卡审核通过','20190313      ','0000000000001096','17:04:59',1);
         * 第六条
         *('2336010788800003902','0000000000000059','20190313',0,'Y','00                  ','20190313','16:57:37','售卡激活','01184239','00000219','00000000','000000000002439958','0059000001562572','2336010788800003902',5000,0,'20190313','17:02:37','1','后台开卡','0000000000000059','00000219  ',null,'00000000','01184238','0059000001562572','20190313','05','0000000000000059',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000000059',null,'0','0',null,'00000000','16:57:37','20190313','17:02:37',null,'0000000001457227','0059000001562572','审核通过                ','购卡审核通过','20190313      ','0000000000000059','17:02:37',1);
         *('2336010788800003902','0000000000001096','20190313',0,'Y','00                  ','20190313','16:57:42','售卡激活','01184242','00000219','00000000','000000000002439961','1096000001562575','2336010788800003902',5000,0,'20190313','17:02:42','1','后台开卡','0000000000001096','00000219  ',null,'00000000','01184241','1096000001562575','20190313','05','0000000000001096',5000,null,5000,0,5000,5000,1,null,'购卡','1','00000219','0000000000001096',null,'0','0',null,'00000000','16:57:42','20190313','17:02:42',null,'0000000001457230','1096000001562575','审核通过                ','购卡审核通过','20190313      ','0000000000001096','17:02:42',1);
         *
         *
         */

        /**
         *
         * update card_active_logs  set cardno='2336010788800003942' where cardno='2336010788800003909' and userid='0000000000000059' and customid='01184257'
         * update card_purchase_logs  set cardno='2336010788800003942' where cardno='2336010788800003909' and userid='0000000000000059'
         *
         *
         * update card_active_logs  set cardno='2336010788800003943' where cardno='2336010788800003908' and userid='0000000000000059' and customid='01184256'
         * update card_purchase_logs  set cardno='2336010788800003943' where cardno='2336010788800003908' and userid='0000000000000059'
         *
         *
         * update card_active_logs  set cardno='2336010788800003944' where cardno='2336010788800003907' and userid='0000000000000059' and customid='01184255'
         * update card_purchase_logs  set cardno='2336010788800003944' where cardno='2336010788800003907' and userid='0000000000000059'
         *
         * update card_active_logs  set cardno='2336010788800003939' where cardno='2336010788800003905' and userid='0000000000000059' and customid='01184223'
         * update card_purchase_logs  set cardno='2336010788800003939' where cardno='2336010788800003905' and userid='0000000000000059'
         *
         * update card_active_logs  set cardno='2336010788800003938' where cardno='2336010788800003904' and userid='0000000000000059' and customid='01184222'
         * update card_purchase_logs  set cardno='2336010788800003938' where cardno='2336010788800003904' and userid='0000000000000059'
         *
         * update card_active_logs  set cardno='2336010788800003467' where cardno='2336010788800003902' and userid='0000000000000059' and customid='01184239'
         * update card_purchase_logs  set cardno='2336010788800003467' where cardno='2336010788800003902' and userid='0000000000000059'
         *
         *
         *
         *
         *
         *
         *
         *
         */
        /*
         * 遍历没有记录的主表卡数据
         *
         */
        $cardArray = M('card_active_logs')->field('count(*) as num ,cardno')->where(['activedate' => ['between', ['20190312', '20190314']]])->group('cardno')->select();
        $array     = [];
        //查出数量大于1的卡号
        foreach ($cardArray as $v) {
            if ($v['num'] > 1) {
                $array[] = $v['cardno'];
            }
        }
        $updateData = [];
        //精确锁定需要修改的卡号
        foreach ($array as $v) {
            //为防止遗漏重复查询
            foreach ($data as $k => $v1) {
                //判断是否存在如果不存在不做任何操作，目的是为获取操作员id与错误卡号
                $result = M('card_active_logs')->where(['cardno' => $v, 'customid' => $v1['customid'], 'activedate' => ['between', ['20190312', '20190314']]])->select();
                if ($result) {
                    $updateData[] = [
                        'oldCardNo' => $result[0]['cardno'],
                        'cardno'    => $v1['cardno'],
                        'userid'    => $result[0]['userid'],
                        'customid'  => $result[0]['customid']
                    ];
                }
            }
        }
        $card_active_logs   = '';
        $card_purchase_logs = '';
        foreach ($updateData as $k => $v) {
            //查询新卡是否存在
            $result  = M('card_active_logs')->where(['cardno' => $v['cardno']])->select();
            $result1 = M('card_purchase_logs')->where(['cardno' => $v['cardno']])->select();
            if (!$result) {
                $card_active_logs .= "update card_active_logs set cardno='" . $v['cardno'] . "' where  cardno='" . $v['oldCardNo'] . "' and userid='" . $v['userid'] . "' and customid='" . $v['customid'] . "' " . "\r\n";
            }
            if (!$result1) {
                $card_purchase_logs .= "update card_purchase_logs set cardno='" . $v['cardno'] . "' where  cardno='" . $v['oldCardNo'] . "' and userid='" . $v['userid'] . "'" . "\r\n";
            }
        }
        echo $card_active_logs;
        echo '</br>';
        echo $card_purchase_logs;
    }

    /**
     * dd
     */
    public function update()
    {
        //查询15日的主卡绑卡记录
        $data = M('ecard_bind')->where(['addtime' => ['between', [ '2019-03-15 00:00:00','2019-03-15 12:00:00']], 'cardno' => ['like', '%23363708888%']])->select();
//        $json  = '[{"cardno":"2336370888801610268","customid":"01191755","status":"1","panterid":"00000286","addtime":"2019-03-15 11:35:12","custid":"01181733"},{"cardno":"2336370888801610292","customid":"00336665","status":"1","panterid":"00000286","addtime":"2019-03-15 11:40:32","custid":"01181798"},{"cardno":"2336370888801610573","customid":"00337145","status":"1","panterid":"00000286","addtime":"2019-03-15 11:59:10","custid":"01183394"},{"cardno":"2336370888801611589","customid":"00337858","status":"1","panterid":"00000286","addtime":"2019-03-15 11:10:24","custid":"01183666"},{"cardno":"2336370888801611423","customid":"01191538","status":"1","panterid":"00000286","addtime":"2019-03-15 11:13:16","custid":"01183932"},{"cardno":"2336370888801610144","customid":"00359534","status":"1","panterid":"00000286","addtime":"2019-03-15 11:48:12","custid":"01180858"},{"cardno":"2336370888801611134","customid":"00337309","status":"1","panterid":"00000286","addtime":"2019-03-15 10:12:12","custid":"01184353"},{"cardno":"2336370888801610631","customid":"00337301","status":"1","panterid":"00000286","addtime":"2019-03-15 10:16:56","custid":"01183484"},{"cardno":"2336370888801610987","customid":"00664617","status":"1","panterid":"00000286","addtime":"2019-03-15 10:37:08","custid":"01184134"},{"cardno":"2336370888801611001","customid":"00337390","status":"1","panterid":"00000286","addtime":"2019-03-15 11:11:20","custid":"01184187"},{"cardno":"2336370888801611175","customid":"00337738","status":"1","panterid":"00000286","addtime":"2019-03-15 11:12:42","custid":"01184258"},{"cardno":"2336370888801610359","customid":"01191614","status":"1","panterid":"00000286","addtime":"2019-03-15 11:21:45","custid":"01181860"},{"cardno":"2336370888801611291","customid":"00272955","status":"1","panterid":"00000286","addtime":"2019-03-15 11:24:57","custid":"01184092"},{"cardno":"2336370888801611613","customid":"00521805","status":"1","panterid":"00000286","addtime":"2019-03-15 11:25:12","custid":"01183600"},{"cardno":"2336370888801610136","customid":"01191706","status":"1","panterid":"00000286","addtime":"2019-03-15 11:28:41","custid":"01180839"},{"cardno":"2336370888801611076","customid":"00336828","status":"1","panterid":"00000286","addtime":"2019-03-15 11:04:02","custid":"01184285"},{"cardno":"2336370888801630035","customid":"00179176","status":"1","panterid":"00000286","addtime":"2019-03-15 11:20:53","custid":"01182444"},{"cardno":"2336370888801630076","customid":"00337089","status":"1","panterid":"00000286","addtime":"2019-03-15 11:03:31","custid":"01182395"},{"cardno":"2336370888801611142","customid":"00366975","status":"1","panterid":"00000286","addtime":"2019-03-15 11:13:50","custid":"01184323"},{"cardno":"2336370888801610847","customid":"01191832","status":"1","panterid":"00000286","addtime":"2019-03-15 11:47:51","custid":"01183940"},{"cardno":"2336370888801610185","customid":"00331176","status":"1","panterid":"00000286","addtime":"2019-03-15 10:03:18","custid":"01180892"},{"cardno":"2336370888801610706","customid":"00366957","status":"1","panterid":"00000286","addtime":"2019-03-15 10:27:39","custid":"01183676"},{"cardno":"2336370888801610383","customid":"01191454","status":"1","panterid":"00000286","addtime":"2019-03-15 11:05:59","custid":"01181912"},{"cardno":"2336370888801610581","customid":"00521853","status":"1","panterid":"00000286","addtime":"2019-03-15 11:35:08","custid":"01183425"},{"cardno":"2336370888801610151","customid":"01190850","status":"1","panterid":"00000286","addtime":"2019-03-15 09:37:01","custid":"01180878"},{"cardno":"2336370888801611563","customid":"01191209","status":"1","panterid":"00000286","addtime":"2019-03-15 10:27:19","custid":"01183679"},{"cardno":"2336370888801611886","customid":"00337476","status":"1","panterid":"00000286","addtime":"2019-03-15 10:33:24","custid":"01183194"},{"cardno":"2336370888801611555","customid":"01191241","status":"1","panterid":"00000286","addtime":"2019-03-15 10:37:24","custid":"01183689"},{"cardno":"2336370888801610722","customid":"01191308","status":"1","panterid":"00000286","addtime":"2019-03-15 10:40:16","custid":"01183694"},{"cardno":"2336370888801610086","customid":"01191251","status":"1","panterid":"00000286","addtime":"2019-03-15 10:40:47","custid":"01180773"},{"cardno":"2336370888801610433","customid":"00331276","status":"1","panterid":"00000286","addtime":"2019-03-15 10:19:33","custid":"01182051"},{"cardno":"2336370888801630092","customid":"00336407","status":"1","panterid":"00000286","addtime":"2019-03-15 11:31:21","custid":"01182342"}]';
//        $data  = json_decode($json, true);
        $model = new Model();
        $model->startTrans();
        try{
            foreach ($data as $key => $v) {
                //根据卡号获取原始会员id
                $cardData = $model->table('card_purchase_logs')->alias('cpl')->join('left join __CUSTOM_PURCHASE_LOGS__ cmpl on cmpl.purchaseid =cpl.purchaseid')->where(['cpl.cardno' => $v['cardno']])->select();
                if (!$cardData) {
                    continue;
                }
                //根据原始会员id查询cid
                $card    = $cardData[0];
                $cidData = M('customs_c')->where(['customid' => $card['customid']])->select();
                if (!$cidData) {
                    continue;
                }
                $customid=$v['customid'];
                $customidOld=$cidData[0]['customid'];
                $cidArray = [];
                $whereCid=[];
                foreach ($cidData as $v) {
                    $cidArray[] = $v['cid'];
                    $whereCid[]=''.$v['cid'].'';
                }
                //根据cid 查询卡号
                $cards = M('cards')->where(['customid' => ['in', implode(',', $cidArray)]])->select();
                if (!$cards) {
                    continue;
                }
                $addtime=date('Y-m-d H:i:s');
                foreach ($cards as $v) {
                    //查询是否已绑定
                   $result= M('ecard_bind')->where(['cardno'=>$v['cardno']])->select();
                   if(!$result){
                       $sql="INSERT INTO ECARD_BIND VALUES('{$v['cardno']}','{$customid}',1,'{$v['panterid']}','{$addtime}','{$customidOld}')";
                       $model->execute($sql);
                   }
                }
                $updateCustomsC="update customs_c set customid={$customid} where cid in({implode(',',$whereCid)})";
                $model->execute($updateCustomsC);
                $model->commit();
            }
        }catch (e $exception){
            $model->rollback();
        }


    }
}



