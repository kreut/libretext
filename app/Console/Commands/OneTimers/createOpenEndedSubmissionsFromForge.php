<?php

namespace App\Console\Commands\OneTimers;

use App\SubmissionFile;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createOpenEndedSubmissionsFromForge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:OpenEndedSubmissionsFromForge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * @var array|array[]
     */
    private $submissions;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->submissions = [
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-24T23:25:02.937+00:00", "userId" => "e8434a95-e6af-409a-a3da-fe655ffb4880", "submissionId" => "6970f42b2636cf0358ad4044"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T23:49:37.357+00:00", "userId" => "30907a35-ee7a-4d65-80d4-b9c8138f27b7", "submissionId" => "6970fde22636cf0358ad417c"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T18:56:45.258+00:00", "userId" => "fcc771d2-2ead-4338-83ae-8d05a0c873b9", "submissionId" => "6970fe382636cf0358ad4183"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-21T18:41:24.295+00:00", "userId" => "aff572e1-7f95-4098-b2a6-a1db2744be28", "submissionId" => "6971050e2636cf0358ad418b"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T16:20:44.879+00:00", "userId" => "47d0484b-bc95-4890-b981-0dd1ca0df4c6", "submissionId" => "6971064d2636cf0358ad41c4"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T20:05:48.377+00:00", "userId" => "c969df1e-027b-4140-b00a-84d6c5d3eaee", "submissionId" => "6971117a2636cf0358ad42ee"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T11:36:38.821+00:00", "userId" => "94cd328e-d1c5-48fe-8bb5-e50b55d3c23e", "submissionId" => "697112842636cf0358ad4340"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-21T18:18:59.901+00:00", "userId" => "8a9bf342-dddd-49af-b19d-e21426ca3215", "submissionId" => "697114972636cf0358ad4439"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T11:35:43.073+00:00", "userId" => "aed02e67-fc28-4f32-bb17-31a989287eb4", "submissionId" => "69711a5f2636cf0358ad4cdd"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T20:31:20.456+00:00", "userId" => "5f749f97-c631-44ca-bc5a-43aa16531859", "submissionId" => "69711c322636cf0358ad4fd7"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-21T20:46:44.818+00:00", "userId" => "65e6047f-7491-4e37-84d9-fc2082e952ae", "submissionId" => "69712b602636cf0358ad6163"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T04:43:04.777+00:00", "userId" => "5fbb8821-2fd9-4a77-b4bb-356315b28d11", "submissionId" => "69712d792636cf0358ad651d"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T07:51:53.000+00:00", "userId" => "1543f57f-5eb6-4510-8227-646acfe8d23b", "submissionId" => "697135ce2636cf0358ad8388"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T20:01:12.372+00:00", "userId" => "89f2aca2-fb5a-4aad-ba74-92c5795272a3", "submissionId" => "69713a692636cf0358ad8dda"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T20:00:32.062+00:00", "userId" => "18684436-f825-4b1d-9b9e-a2e52753cf00", "submissionId" => "697155cf2636cf0358ad9caa"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T00:47:52.413+00:00", "userId" => "9c9e25f8-c800-4c2c-bc1b-383dfaf9ad90", "submissionId" => "6971640f2636cf0358ada0c9"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T00:45:35.627+00:00", "userId" => "25c95d0e-c163-44fb-83c3-822544f2b42b", "submissionId" => "6971712e2636cf0358adb201"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T01:06:36.324+00:00", "userId" => "4275c014-e172-4c8c-bd77-b76d48bbf1b7", "submissionId" => "697175ac2636cf0358adb7d6"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T01:26:08.990+00:00", "userId" => "af29d6d7-aa36-4ba6-83ca-b517e3167069", "submissionId" => "697177ff2636cf0358adb9fc"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T21:07:04.877+00:00", "userId" => "34006688-6f49-446f-9ddd-abb681a23a21", "submissionId" => "697182792636cf0358adc13b"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T01:21:44.979+00:00", "userId" => "eac55de1-a314-4e45-9ab1-c06481910ea3", "submissionId" => "69719b0c2636cf0358adc301"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T01:46:59.495+00:00", "userId" => "fbb591bc-c3be-4de8-8edd-51e86aa43af4", "submissionId" => "6971ab072636cf0358add63b"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T22:53:13.558+00:00", "userId" => "86f0ec12-bd33-4b12-be18-050b983be3da", "submissionId" => "6971ac5b2636cf0358adda7f"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T06:57:11.539+00:00", "userId" => "b7feef3b-24ca-4334-80b5-fe8fb619424d", "submissionId" => "6971b2122636cf0358ade65c"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T02:57:36.921+00:00", "userId" => "edab44ca-3b7f-4959-8db4-d1496b250ee3", "submissionId" => "6971b9a12636cf0358adf149"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T07:49:36.676+00:00", "userId" => "8ca209d7-f7ed-46d8-8517-fc04c9a2a4ef", "submissionId" => "6971bb972636cf0358adf619"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T07:56:28.017+00:00", "userId" => "dd768a82-6ff7-4671-8107-737473f7f6fd", "submissionId" => "6971c4812636cf0358ae0492"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T09:51:59.054+00:00", "userId" => "34284dfe-1e80-4501-8233-95a7ae235a81", "submissionId" => "6971dbe42636cf0358ae4810"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T20:35:40.796+00:00", "userId" => "4b3ed038-e213-4d5b-ab31-71f6a117a450", "submissionId" => "6971fbe22636cf0358ae4edb"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T16:19:42.379+00:00", "userId" => "15dab13a-b9bc-4460-a511-ad22c38787a1", "submissionId" => "69724ab32636cf0358ae8b1e"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T18:52:31.557+00:00", "userId" => "bdd2eb37-3fbd-4d42-b423-b4825f5ac5de", "submissionId" => "697270c32636cf0358ae983d"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T10:01:48.866+00:00", "userId" => "9df65f99-6a13-45d4-a3fc-822786543073", "submissionId" => "69727c912636cf0358aead96"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T20:14:20.031+00:00", "userId" => "71aeef41-cd98-463c-8669-632e6f93a5ff", "submissionId" => "6972849e2636cf0358aec537"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-22T21:15:06.242+00:00", "userId" => "559af596-b923-4eb0-ba14-0df4a2d91de3", "submissionId" => "697292c32636cf0358aeda45"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T22:37:39.337+00:00", "userId" => "bb4feae3-4812-4852-8015-155cb1cc7b15", "submissionId" => "6972a9ea2636cf0358aef4e2"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T12:30:37.626+00:00", "userId" => "685c8f22-1800-4f65-a658-1b7ff3e7082a", "submissionId" => "6972bbc92636cf0358af03fd"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-23T00:29:05.791+00:00", "userId" => "45a052dd-f89a-4b4d-910b-5ca694f2fecd", "submissionId" => "6972bf642636cf0358af0a58"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T18:57:59.704+00:00", "userId" => "c1e90d02-0e81-457d-b636-31944751c0f8", "submissionId" => "6972bf852636cf0358af0a5f"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-24T22:00:22.012+00:00", "userId" => "1fbf4165-4522-46d3-a1e1-46556096f7d4", "submissionId" => "6972c0a82636cf0358af0c92"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T18:59:11.259+00:00", "userId" => "9efeb96c-4e60-4bf5-9d09-5ceb5c3fd86c", "submissionId" => "6972c0d32636cf0358af0ce9"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-23T01:01:34.189+00:00", "userId" => "60eb7261-e69c-460f-af80-067f728518d9", "submissionId" => "6972c7442636cf0358af1cfe"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-23T03:50:09.141+00:00", "userId" => "c87cf03b-f291-4135-b222-af91d527cbd1", "submissionId" => "6972edcd2636cf0358af3fc1"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T05:04:12.134+00:00", "userId" => "f56a40f3-4ca1-4878-acc9-dcdabdac24dc", "submissionId" => "6973b9a92636cf0358afc033"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T04:32:46.407+00:00", "userId" => "0c1f32b9-0e0d-4362-a3bd-fa035a88a9e1", "submissionId" => "6973bb5f2636cf0358afc03a"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-24T22:12:04.031+00:00", "userId" => "d9684cc0-35c7-46ac-abfe-87d89afa1861", "submissionId" => "6973c4ec2636cf0358afc3c7"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T10:51:52.510+00:00", "userId" => "e0411314-30eb-4f17-915f-07cf43e98a6a", "submissionId" => "6973c9412636cf0358afcb08"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-23T21:37:04.810+00:00", "userId" => "85e20175-0224-4686-992c-8cc21015f125", "submissionId" => "6973e9cb2636cf0358afebf9"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T07:52:26.005+00:00", "userId" => "ae3a5643-8ded-4f4d-9e02-a0338220bec7", "submissionId" => "6974078f2636cf0358b01522"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-24T18:59:51.781+00:00", "userId" => "0a692177-caa7-465c-8989-d91ed5151b0e", "submissionId" => "697410bc2636cf0358b0218c"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-24T07:47:56.849+00:00", "userId" => "fde2f321-949d-4139-98a1-86158cfd3f0f", "submissionId" => "69741ebb2636cf0358b037d3"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-24T04:47:02.406+00:00", "userId" => "6272bccd-b3e7-455b-9cc8-313525b950d0", "submissionId" => "69744c5c2636cf0358b06a97"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T11:43:45.646+00:00", "userId" => "a8de16b5-8ece-4f7f-88e3-aa77c39b2f3c", "submissionId" => "697460532636cf0358b09d33"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T07:02:50.678+00:00", "userId" => "12293c65-c86d-443e-870c-8b4dd2c762af", "submissionId" => "697536262636cf0358b193a4"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T06:33:31.197+00:00", "userId" => "54c81f88-0a0a-4ed0-a353-26237be13487", "submissionId" => "69753f892636cf0358b1b0d2"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T03:28:12.057+00:00", "userId" => "4df48e38-1140-437c-a8fe-0289df4cec68", "submissionId" => "697562c82636cf0358b229e6"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T22:06:21.729+00:00", "userId" => "722949cc-1ff3-4f04-9826-99aac021dfb0", "submissionId" => "697567d72636cf0358b23474"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T17:13:59.567+00:00", "userId" => "3223404a-d2bb-40f7-921c-64008075da30", "submissionId" => "697574c22636cf0358b27a9f"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T06:03:16.410+00:00", "userId" => "543d192a-7dae-44da-b31d-8d34b0083245", "submissionId" => "6975761a2636cf0358b2809b"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T10:22:33.641+00:00", "userId" => "6f2e3fe5-424d-47c5-a358-8beb24089a38", "submissionId" => "69758ea82636cf0358b30a31"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-28T02:50:38.535+00:00", "userId" => "08eef870-f37a-401f-9e5a-02c1d0b964d9", "submissionId" => "697597842636cf0358b32090"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T20:10:28.872+00:00", "userId" => "58809869-a477-447a-bbfc-25b28f8f1170", "submissionId" => "6975a04f2636cf0358b349c5"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T05:04:09.803+00:00", "userId" => "2d6e9c75-7ce3-4e5a-88ac-c60a0c1ad440", "submissionId" => "6975a3a42636cf0358b36474"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T11:18:06.709+00:00", "userId" => "2799a2e6-275e-4ac9-ad73-35ad6bb23a28", "submissionId" => "6975aa842636cf0358b38583"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T17:32:21.931+00:00", "userId" => "47278dcf-e8c0-4456-9ced-5f8e65f6a152", "submissionId" => "6975b9d32636cf0358b407b2"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T07:41:17.822+00:00", "userId" => "500b6873-0c28-4df7-8fe5-2f546f8e92df", "submissionId" => "6975bfae2636cf0358b4473d"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T08:01:27.236+00:00", "userId" => "5bab303f-2ffd-4d69-b707-65a84d369818", "submissionId" => "6975cc382636cf0358b4d40b"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T08:01:08.306+00:00", "userId" => "3eb5d41a-2c27-40cb-9e32-43ae09aeda05", "submissionId" => "6975cd492636cf0358b4d88b"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T14:15:11.318+00:00", "userId" => "d7842560-a471-4228-b787-4db8359438e7", "submissionId" => "697624a92636cf0358b63b1f"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T22:25:32.083+00:00", "userId" => "3f9503f4-ac1a-48a2-a9ad-a541afba6fab", "submissionId" => "69764d8b2636cf0358b66bc8"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T19:16:19.449+00:00", "userId" => "4b9f3782-07bb-42e5-8a8b-6385864aa614", "submissionId" => "697651932636cf0358b679df"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T20:04:00.449+00:00", "userId" => "072c0c0d-4396-4018-903c-a655f6235a14", "submissionId" => "6976657a2636cf0358b71e2d"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T22:28:40.102+00:00", "userId" => "84cc4dba-33c9-4f0d-adb7-ebc8682c2ac0", "submissionId" => "697668982636cf0358b73ba7"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-25T20:24:38.066+00:00", "userId" => "26873de5-6481-4cc0-8b3e-421915047d57", "submissionId" => "69767b142636cf0358b7c773"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T01:30:46.371+00:00", "userId" => "867b7e11-fc6f-4213-acc6-49353fb61be3", "submissionId" => "6976c1122636cf0358b86e86"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T04:07:05.784+00:00", "userId" => "86e621c4-52cb-4921-bcc1-46825f6d069d", "submissionId" => "6976e2562636cf0358b8fa8f"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T05:56:47.463+00:00", "userId" => "107753830928131887062", "submissionId" => "6976ed802636cf0358b907d2"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T05:59:17.613+00:00", "userId" => "89437090-69e4-4449-a0dd-9ec4a4b40c9d", "submissionId" => "697701e22636cf0358b9258b"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-26T06:57:07.364+00:00", "userId" => "82d81150-cdc1-42dd-a8e4-68d1afb1f885", "submissionId" => "697710092636cf0358b938ed"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-01-30T21:01:12.211+00:00", "userId" => "22503463-8a3f-4ed0-a0d5-75f16e3e929e", "submissionId" => "697c97347b86d7029f056845"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-02-04T07:44:59.159+00:00", "userId" => "a4ec95bb-73ad-40b4-9db1-3fab5021b74f", "submissionId" => "6982f7bfd2bc5a8306939e94"],
            ["questionId" => "6970d3f12636cf0358ad403f", "timestamp" => "2026-02-10T06:14:26.490+00:00", "userId" => "61d06864-e94f-47ef-be65-45f95206b356", "submissionId" => "69852797cb1c83e4c8f8dd9d"],
            // Second dataset
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T06:33:15.745+00:00", "userId" => "1a96a486-bc1a-432e-b543-69f908d8d93f", "submissionId" => "696183d22636cf0358a64cfc"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T01:56:19.367+00:00", "userId" => "0e692bf4-0fec-4111-81a8-065a5cbf3d1b", "submissionId" => "6962d2bf2636cf0358a64f10"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T20:00:26.184+00:00", "userId" => "967db0e5-2f80-4955-b866-c7eef61f6864", "submissionId" => "696342332636cf0358a64f19"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T20:03:42.230+00:00", "userId" => "6ceca3af-846b-4f0f-b6bc-238c865a78ad", "submissionId" => "696462522636cf0358a65d14"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-02-09T23:15:04.070+00:00", "userId" => "fbf4590e-30fa-4b05-a27d-213a718476a1", "submissionId" => "696542f92636cf0358a6610c"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T05:36:15.539+00:00", "userId" => "23d5b4b9-7c30-4eba-9c6b-d96449d19f1e", "submissionId" => "696582832636cf0358a669bd"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-24T06:25:04.323+00:00", "userId" => "259f9fd8-4bf5-4f0f-9304-8d0c61707ffb", "submissionId" => "6965d3492636cf0358a67efc"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-24T03:17:03.471+00:00", "userId" => "0e110421-b3ef-4f6c-bf19-41a4e44da5f9", "submissionId" => "69685fd12636cf0358a6cec5"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T02:49:35.058+00:00", "userId" => "3df80746-30c8-4638-88f4-6d9130edc944", "submissionId" => "696947072636cf0358a71021"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T04:45:33.524+00:00", "userId" => "b852d11f-68a9-4019-8798-6ab954c88c99", "submissionId" => "696dc37d2636cf0358a9e673"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-26T08:33:46.893+00:00", "userId" => "64a80c0a-4f98-472d-94ac-f665c7e45aec", "submissionId" => "696dcc092636cf0358aa23ec"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T20:47:37.080+00:00", "userId" => "ada4ab92-4a50-404d-9c93-87cd112b3607", "submissionId" => "696dd7a02636cf0358aa3d26"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-25T10:15:19.604+00:00", "userId" => "53e25651-2ba4-4ee0-92b5-49b564cd5add", "submissionId" => "697598e92636cf0358b32581"],
            ["questionId" => "696170b72636cf0358a64cc5", "timestamp" => "2026-01-26T12:03:21.911+00:00", "userId" => "8aaac662-e6df-4f2d-a4b5-aa3c76b4bc38", "submissionId" => "697714c82636cf0358b942f4"],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $enrollments = DB::table('enrollments')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('course_id', 7361)
                ->select('user_id', 'central_identity_id', DB::raw('CONCAT(last_name, ", " , first_name) AS student'))
                ->get();
            $central_identity_ids = [];
            foreach ($this->submissions as $submission) {
                $central_identity_ids[] = $submission['userId'];
            }
            foreach ($enrollments as $key => $enrollment) {
                if (!in_array($enrollment->central_identity_id, $central_identity_ids)) {
                    unset($enrollments[$key]);
                }
            }
            $submissionsByUserId = [];
            foreach ($this->submissions as $submission) {
                $submissionsByUserId[$submission['userId']] = $submission;
            }
            $count = 0;
            foreach ($enrollments as $enrollment) {
                $submission = $submissionsByUserId[$enrollment->central_identity_id];
                $dateSubmitted = Carbon::parse($submission['timestamp']);
                SubmissionFile::updateOrCreate(
                    [
                        'assignment_id' => 136930,
                        'question_id' => 284066,
                        'user_id' => $enrollment->user_id,
                    ],
                    [
                        'type' => 'forge',
                        'original_filename' => '',
                        'submission' => '',
                        'date_submitted' => $dateSubmitted,
                        'upload_count' => 1,
                    ]
                );
                $count++;
            }

            echo "Done! Total updated: {$count}\n";
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;
        }
        echo 'Done!';
        return 0;
    }
}
