<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Track;
class TrackSeeder extends Seeder {
  public function run(): void {
    \$rows = [
      ["code"=>"SAPPORO","name"=>"札幌"],["code"=>"HAKODATE","name"=>"函館"],
      ["code"=>"FUKUSHIMA","name"=>"福島"],["code"=>"NIIGATA","name"=>"新潟"],
      ["code"=>"TOKYO","name"=>"東京"],["code"=>"NAKAYAMA","name"=>"中山"],
      ["code"=>"CHUKYO","name"=>"中京"],["code"=>"KYOTO","name"=>"京都"],
      ["code"=>"HANSHIN","name"=>"阪神"],["code"=>"KOKURA","name"=>"小倉"],
    ];
    foreach(\$rows as \$r){ Track::updateOrCreate(["code"=>\$r["code"]],["name"=>\$r["name"]]); }
  }
}
