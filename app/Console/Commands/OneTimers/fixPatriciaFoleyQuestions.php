<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixPatriciaFoleyQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:PatriciaFoleyQuestions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $identifiers = ['g0125335cf9a79267e17865986b86dd51', 'g03f9614b73f0adebe63fddebabcc56c1', 'g04260114f656adc4d4b009ece76ce941', 'g044eae69f71f53c638c7aded17352421', 'g046e72079c243f0a8b74ba48066413ae', 'g069f414437edcf641990a5f91266d06f', 'g074a0d0ae85ce8dad8346a6b57290306', 'g07851e10e4ced56fbf9cf30fcbfd3875', 'g07a828e8cf51aacd38576b70d344c258', 'g08c307f33e0891d9ac9f011416877246', 'g0a4eb70f907a81fb137e31f45694b215', 'g0a5c333618f62a7643d1ecf1d7e618d5', 'g0d68d8c065c026a0fbedf50faea85762', 'g0df7a32bc6d65b687a40ca33b7ac2280', 'g0eec91ef60b86695c36810edeca9c52a', 'g1002ce49b64f9e893a65a8258a72df1e', 'g101967b039b527b757fc42979d70f57c', 'g1105ea74c35ec8db12a0ecb8595f73af', 'g1183dfaafbbb155845e86021c40321b9', 'g152492f453d66c9ba9a32523af6d8909', 'g16813e9c71cee6936f039cc82c01bdc5', 'g16a44840cf5165c3c4ddf2400467adc7', 'g16c8cf65a2b029e4ebe19d9886a1c7b2', 'g1794086466b66450070caf44b9d655fe', 'g17b8e1900b86bc7bc76cf8a712e5ebd7', 'g1ade28b181946a8f34c316ea00ec7953', 'g1b241723014f9de796578ffee311a643', 'g1c280a81a98c01e7a1fb38678d78e575', 'g1c7bed57c01a2063ba34b08bcfeeb8ba', 'g1d06d9faae4fa0e870622b39bfcbc822', 'g1d6d659b2767ab5151a80755fb2c5fa6', 'g1f7ce98ff2a9579999164945953476b0', 'g205e0f33a429677f9fb8d1eff594073f', 'g219f1358e822ae1cb1bbd6aa969eef6d', 'g26cd13889393e01ad4b57a420b766dac', 'g28954469bda967b227953bb8b67fa7e3', 'g291682ecef458764e8765eb2a7a95df3', 'g2a9c02704b1a25739b4da9423198572b', 'g2db49aaede4b727b1565e65294cf60cf', 'g2fbbcda3a81be343076675ea27ed4aa2', 'g30061a4bd63c2fc2ac8f4a133840eed3', 'g30408841e79d494ddc633d84889c1a13', 'g33acf0e8fd63c71548c50a7ccc00238c', 'g33d6a12da0d2bfd5a9853e2633cc1950', 'g340fead479c70fe5d8b4ab82a110e2e8', 'g3741a377db75ee040c1d8d68ac829cc6', 'g3799ea2d47c7c56fbdb658eb75e74059', 'g39ed4d2619cb5b238d408cf3f53060ea', 'g3a209a83ec524ab2cafa9fe7b2328697', 'g3ac934a621acfa95d5bc09ff56d4e1c3', 'g3b36e3b0683f78183f0059e3965e4553', 'g3b86bedd173df78e4f3f8b3cf103f542', 'g3b9bc55f300c7622fa5a905b80122edc', 'g3d045e0e7b930255af2415abc1b75bc6', 'g3e39a0ce82f3b12e1f638d5ce04e5428', 'g41c2bac1c8044e2f4cca813d37269d90', 'g41e9093e23c6eaacbd9cfdfa76047b4d', 'g4267e93fad0620e9f8822a6383ae166d', 'g43e128c7a90822002d078c1ae3b80b82', 'g44d2c623580648f6b54c9d8af92b8335', 'g4841cdd54e28fbd0037d612815413755', 'g48ac2ac2ac7e3de537f0309679665ac7', 'g49a642d384114c1a651f7c0da0e3802b', 'g49bdd07447387000286e6c545db6c53f', 'g4a24f7b70408333dacd525ba739a0c7d', 'g4a55036dcc54b51ade534a910b031df4', 'g4b480dda408d573e81993739aa11ad4c', 'g4c13cb606c9033e512fb7fe74d0ad3aa', 'g4ca75b955623a7e542420c001839c520', 'g4f5a050b3f937ff5b56e29b1da34140a', 'g4fa764c5982201c43315d7d1f87eaa28', 'g4fc712752e01ab537d7e9d827d99d09c', 'g4ffaf5c4df948ff2fe2af0c5d7075ef1', 'g5226e1e327ae1eb27f23e5c9c4681b26', 'g53c6cdb40037315920101f794499a7ef', 'g57094278f5f63f4b38e6968dfbcf8430', 'g574f906c002bb68a8b3b5b5303f940e6', 'g575e48d39ec00f97ebab533e25c7fe5d', 'g57f5e40c2c7fece55c57ab9b5bac5999', 'g5867b4eae168e80b05b72601f7a99b40', 'g5973ea3e289852a3b674429e0d9f69dd', 'g5aadb22f935c3e59ba6b9bde9b78e4db', 'g5c207e3f5432a095ed5b0bea5f138e82', 'g5ceff775e4748f0f5768ca96b8275690', 'g5df06818184f2e385998edbbe0c1556c', 'g5fe29f0e0d9e7d224a107779f3343787', 'g6051811c0a2c64a29f4ce0fe356ea095', 'g6108027046a903b393e0ed803a0e3afa', 'g61498e0cc4ba4c262be25f3d7e165030', 'g622c1f0031e146a05ef94b7347b5474d', 'g633c22bdb4a7c5d35dc558690753bbe7', 'g636208dd99029726bddbeb767e124517', 'g643cd6d6d8a294f0c9084c563d352b07', 'g643d28b1b708a436fd709aa58f1cda9f', 'g655ff2bdc118b06b1e60fb00ff8ab12e', 'g6613ba1d9b0eead0d71e51a25b087af2', 'g687e81dc6e9b06822ce4226735ef70d6', 'g6affda2bfa024e2f4b01bdd8ddead89f', 'g6ca92611b7c73fd8d37dd099f7661d60', 'g6cb02ffda8dc5fd6ab9e349395beb558', 'g6cebd1214d500c5b32124432fa4e38d1', 'g70482e998ba0d0a08f181675f6638abe', 'g706d85b6363da088e5f90c58fa13c498', 'g73f0ac309e0770874afb5ac9ecd94d5e', 'g74b20806cfab3c9652681a70499a09d2', 'g7652a0d39bdc191f7ede9f51169c264e', 'g792447f8c3ef98d3be2a0cda72bd5c41', 'g7950d2849b4d99b37b0e7e3cb50db999', 'g7954eb4edb8e8916662f005a18e71768', 'g79612756df53f0222b1431b5bd510a48', 'g7cacbc25b6776c8bf54386b25c86ce81', 'g7cd3916d2a290d9e2956d99da77176a4', 'g7ce3f8cb7f55955b632dcac8a0c0a075', 'g7dab3e599fae05ba55baaeba9f68a506', 'g7f62b3b75d067aef4d2c2950d00cc433', 'g862d80b9db898f37cf17e1552b5a298c', 'g868e77d8fc1e2537d8bb1295c2aa4a10', 'g8755673af15fc260cc89b8408aadd68e', 'g891ee2e641e76b6ffed92f4209a2e6e4', 'g895023bfdaaf6e1459d0ce376923db14', 'g8aa8d423a6afe79be5a50e201aa9b615', 'g8baaceb99f38ad7ee442185856afd1d2', 'g8cd796f9d751d003d1ebf3e4ea312a2b', 'g8da16cbd9295d8ec74cf53ef5522db4d', 'g8efd78e1c67b4b54a3e13644e6466fcb', 'g8f3608dc6bd5a9e2aff0c26a9e474f69', 'g9312eadf2270238069990b8aa8bd5f7e', 'g958e91b6af45b8668a7121c0671f8dd0', 'g965b254fc27a47cb5a8ff70db6599d68', 'g97b25df88c8059326e88fe46801be39a', 'g9a707963c3d871e5b2ffd3eeea065adb', 'g9c286501a92394409706abe85474f095', 'g9de214dffc592ed1b3059f074be16fc1', 'g9de808cfa1fd813266cfee68ec17b4d5', 'g9e31c5b500f7d0471f9275fd8f95a86e', 'g9fc157e1e5d3d89d42a8432f146e01d8', 'ga002ce2d36e9931dc4201afb26ea2a4c', 'ga063f1896c955e49e320ab7b1504a9db', 'ga0ce558a251c0e3d627fdbffa98d7257', 'ga3724547345afc3e045d7a1b93908e30', 'ga62424b03e22143df62cb3109d176b8d', 'ga858841acbcac4ab578207fa82e8d592', 'gaa4eda621e7d2e38d898dc394bdc60dd', 'gaaf0496d5df34993f8a77f247cdf87fc', 'gaca39f98de97b66f30029acbc39341dc', 'gacfbf477462e03e89c07c08e6deed371', 'gad3baf5eef64d4cf30aeeab0644d0972', 'gadec799b6d8d4d63c034380db1033ca3', 'gaf6d6f8823c906bf37cd2c16d8f500f6', 'gaf6f89e8e7055d175ef3b32209cfe018', 'gb0094f419cf75e30f3bc521b1edf8c0a', 'gb071c5b3ed0043252c39101307527b71', 'gb31c229f0a7f39a4efe87a2dad99f2eb', 'gb32dc0abe657c891195e9b24ffe163aa', 'gb6581963875c41283564c5da558151e5', 'gbf85cc1e95d8723680f931eae2e02848', 'gc0d417b5539e3d6a126e79e32a991c8c', 'gc1525ddd96739166960c8a4f7687dada', 'gc34b609075a0ad3862273bf801d00720', 'gc3d1035098f55d6c5730956d1bf8d0d2', 'gc5bbb007cc0a7d284fe8fa0fcb683c2d', 'gc64c93fb975e797becb85b94b8cc7474', 'gc74db3a88818b472165beea859749f4e', 'gc882c97afdc72d296db1104752f877d8', 'gc952e8bcd1991a25de0f3271d80dbdc4', 'gc97d4f6c802faf014e82145822e26a0a', 'gca1387d5343a9ac676193098ab22838c', 'gcad026a0f8ad8b8e462a186948508070', 'gcb2a961ff05fd4d54500a08478bd175c', 'gcb623458a91a44e3ef1c2fa0af2de91c', 'gcd1fdc94a93c2707eb0b8292b29853cc', 'gcf8c37c8a0a0b9dad2fddb5f3ebb2fd5', 'gd05acff3ae05d877a5799fd7cb908782', 'gd221e203b0386754d04930d8fe67f02a', 'gd3952cc52d0649f930643b09f11faf31', 'gd4b53ded0429548cfa5a03c39e51cd25', 'gd693e68d9f99bef90c07891ce21d0706', 'gd9fc71922de3e79afc5c3e2110803dcb', 'gda88a8b42b2cc109ad64e6d698e741d1', 'gdb6131e12b3c6ddc040ef8e54974f61f', 'gdc136a65d9ec8b6e8a3b65cf7b25f5cd', 'gdec01414a92b6f2275647c0807124a90', 'gdf0615d63310235fb851257e6ee0df23', 'gdf352422bc0376e1163bd7a02185084c', 'gdfa4dddbf8995992cd1dbf6418504795', 'ge1e0301ce3de6c389eee5313405a606f', 'ge1e1a372f24870b0cb2b46fa678167c3', 'ge1eee802c93f79bcf1404c465f0b8b6d', 'ge27481d82e871751b639e683af427841', 'ge3f6704414d48be67960a0ae6085463e', 'ge4c6c13acf3259a372da60215b4839c3', 'ge4e9fd91e8c9879e9a7b615cc9af6c7d', 'ge4f973182cfb1ff8e926c5dcc08b1d11', 'ge5682fd116a5f76afca70b81976037e4', 'ge71029e2df4004d152d9bcd160ef63fa', 'ge7720fa1809cdcf61a49a0b3becb1e24', 'ge7fc0202a69a4779347e392e404a0c79', 'ge81a9b7f592b49ad11fd6b1212ba142c', 'ge871c8732e86caa51076c8b5fac128ff', 'ge9754e705c989b7eae91266a5126462e', 'gea3291085c2dc0a2b69f19e9a5468d1f', 'geb85cb9934884ccba159842de8a07e81', 'gec0d4b32c52d0099021539d265578ccd', 'geca451c1cda2bfb0eb366ae361a0122e', 'gee48ccadd1e37e780f2b73df1e874137', 'gef46c607fa4b5f52fab1fa8008d50343', 'gef6168a7fa53846a4ebbfcfa5cee347a', 'gf11014a9a0d486d78fbe09e401a63341', 'gf2ac67ab20a614b1d7f006023365e1b7', 'gf3f88af7e3bb6858de34acece338d7ca', 'gf71b5d62c9bedeff8625066454248e26', 'gf7316b5ea06a354d33f977281964c05f', 'gf863c5264b8882d06f016681860fa2b7', 'gf9418d9c69e8e9be7b5db3de82f0efb8', 'gfa468ee3a47bc6fe739d3fb338e7d385', 'gfbd9337e36c982d07081e145774ddef3', 'gfc7d4a20d0db4bc139f8b5cb5f52ddd7', 'gfe2c9d8127ebbb29b765af285b484dac'];  // DB::table('qti_imports')->whereIn('identifier')
        $question_ids = DB::table('qti_imports')
            ->whereIn('identifier', $identifiers)
            ->whereNotNull('question_id')
            ->get('question_id')
            ->pluck('question_id')
            ->toArray();
        $questions = Question::whereIn('id', $question_ids)->get();
        try {
            DB::beginTransaction();
            foreach ($questions as $question) {
                $question->folder_id = 2948;
                $question->question_editor_user_id = 1982;
                $question->save();
                echo $question->id . "\r\n";
            }
            DB::commit();
        } catch (Exception $e) {
            echo $e->getMessage();
            DB::rollback();
        }

        return 0;
    }
}