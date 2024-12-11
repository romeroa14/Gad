use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignMetricsTable extends Migration
{
    public function up()
    {
        Schema::create('campaign_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ads_campaign_id')->constrained();
            $table->date('date');
            $table->integer('impressions');
            $table->integer('clicks');
            $table->decimal('ctr', 8, 4);
            $table->decimal('spend', 10, 2);
            $table->integer('reach');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_metrics');
    }
} 