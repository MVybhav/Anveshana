<?php
// itinerary.php - Smart AI-powered itinerary generator
session_start();

// Handle form submission and AI call
$itinerary = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destination = trim($_POST['destination'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $interests = trim($_POST['interests'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    if ($destination && $start_date && $end_date && $interests && $budget) {
        // Simple rules-based itinerary generator
        $days = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
        $interestList = array_map('trim', explode(',', $interests));
        $sampleActivities = [
            'adventure' => [
                'Morning: Thrilling Trekking Expedition', 
                'Afternoon: White Water River Rafting', 
                'Evening: Paragliding with panoramic views', 
                'Full Day: Ziplining through the forest canopy'
            ],
            'culture' => [
                'Morning: Explore historical landmarks and museums', 
                'Afternoon: Participate in a traditional cooking class', 
                'Evening: Enjoy a local cultural show or performance', 
                'Full Day: Guided city heritage walk'
            ],
            'food' => [
                'Morning: Breakfast at a local cafe', 
                'Afternoon: Street food tour with a local guide', 
                'Evening: Fine dining experience at a top-rated restaurant', 
                'Full Day: Culinary workshop focusing on regional cuisine'
            ],
            'nature' => [
                'Morning: Bird watching and nature photography', 
                'Afternoon: Visit a botanical garden or national park', 
                'Evening: Sunset viewing at a scenic viewpoint', 
                'Full Day: Explore a wildlife sanctuary or reserve'
            ],
            'shopping' => [
                'Morning: Browse local artisan markets for unique souvenirs', 
                'Afternoon: Explore modern shopping malls', 
                'Evening: Visit antique shops and art galleries', 
                'Full Day: Guided shopping tour for local specialties'
            ],
            'relaxation' => [
                'Morning: Rejuvenating spa and wellness session', 
                'Afternoon: Yoga and meditation class', 
                'Evening: Relax by the beach or poolside', 
                'Full Day: Leisure day at a luxury resort'
            ],
            'history' => [
                'Morning: Visit ancient ruins and historical sites', 
                'Afternoon: Explore a historical museum', 
                'Evening: Attend a historical reenactment or storytelling session', 
                'Full Day: Guided tour of a historical city center'
            ],
            'beach' => [
                'Morning: Relax on the sandy beaches', 
                'Afternoon: Water sports like snorkeling or jet skiing', 
                'Evening: Beachside dinner with live music', 
                'Full Day: Island hopping tour'
            ],
            'mountains' => [
                'Morning: Scenic mountain drive and viewpoint visits', 
                'Afternoon: Cable car ride to mountain peaks', 
                'Evening: Stargazing from a mountain lodge', 
                'Full Day: Alpine hiking and picnic'
            ]
        ];
        $itinerary = "\n--- Personalized Itinerary for $destination ---\n";
        $itinerary .= "Dates: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date)) . " (" . $days . " days)\n";
        $itinerary .= "Budget: ₹$budget\n";
        $itinerary .= "Interests: " . implode(', ', $interestList) . "\n\n";

        for ($i = 0; $i < $days; $i++) {
            $date = date('l, d M Y', strtotime($start_date . "+$i days"));
            $itinerary .= "### Day " . ($i+1) . ": $date\n";
            $itinerary .= "------------------------------------\n";
            $usedActivities = [];
            foreach ($interestList as $interest) {
                $key = strtolower($interest);
                if (isset($sampleActivities[$key])) {
                    $activity = $sampleActivities[$key][array_rand($sampleActivities[$key])];
                    if (!in_array($activity, $usedActivities)) {
                        $itinerary .= "- " . $activity . "\n";
                        $usedActivities[] = $activity;
                    }
                }
            }
            if (empty($usedActivities)) {
                $itinerary .= "- Explore local attractions at your leisure\n";
            }
            $itinerary .= "- Evening: Enjoy local cuisine and relax\n\n";
        }
        $itinerary .= "--- Important Travel Tips ---\n";
        $itinerary .= "- Pack according to the weather and activities.\n";
        $itinerary .= "- Stay hydrated and carry necessary medications.\n";
        $itinerary .= "- Keep your travel documents safe.\n";
        $itinerary .= "- Embrace the local culture and enjoy every moment!\n";
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Itinerary | Anveshana</title>
    <link rel="stylesheet" href="css/signup.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .itinerary-container { max-width: 700px; margin: 40px auto; background: #fff; border-radius: 15px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); padding: 3rem; }
        .itinerary-title { text-align: center; color: #333; font-size: 2.5rem; font-weight: 700; margin-bottom: 2rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.05); }
        .itinerary-form label { font-weight: 600; color: #555; margin-bottom: 0.6rem; display: block; font-size: 1.05rem; }
        .itinerary-form input[type="text"], 
        .itinerary-form input[type="date"], 
        .itinerary-form input[type="number"], 
        .itinerary-form textarea { 
            width: 100%; padding: 0.85rem 1.2rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; 
            background: #fdfdfd; margin-bottom: 1.5rem; transition: all 0.3s ease; box-sizing: border-box;
        }
        .itinerary-form input:focus, .itinerary-form textarea:focus { border-color: #5f27cd; box-shadow: 0 0 0 3px rgba(95,39,205,0.1); outline: none; }
        .itinerary-form button { 
            background: linear-gradient(45deg, #5f27cd 0%, #bb27cd 100%); color: #fff; border: none; 
            padding: 0.9rem 2.5rem; border-radius: 8px; font-size: 1.15rem; font-weight: 600; 
            cursor: pointer; transition: all 0.3s ease; width: 100%;
            box-shadow: 0 4px 15px rgba(95,39,205,0.2);
        }
        .itinerary-form button:hover { 
            background: linear-gradient(45deg, #bb27cd 0%, #5f27cd 100%); 
            box-shadow: 0 6px 20px rgba(95,39,205,0.3);
            transform: translateY(-2px);
        }
        .itinerary-result { 
            background: #f0f8ff; border-radius: 12px; padding: 2rem; margin-top: 2.5rem; 
            white-space: pre-wrap; font-size: 1.05rem; color: #333; line-height: 1.8;
            border: 1px solid #e0eaff;
        }
        .itinerary-result h3 { 
            color: #5f27cd; font-size: 1.8rem; margin-bottom: 1.5rem; text-align: center;
            border-bottom: 2px solid #e0eaff; padding-bottom: 1rem;
        }
        .itinerary-result h4 { color: #5f27cd; font-size: 1.3rem; margin-top: 1.5rem; margin-bottom: 0.8rem; }
        .itinerary-result p { margin-bottom: 0.5rem; }
        .itinerary-result ul { list-style: none; padding-left: 0; }
        .itinerary-result ul li { margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative; }
        .itinerary-result ul li::before { 
            content: '•'; color: #5f27cd; position: absolute; left: 0; 
            font-weight: bold; font-size: 1.2rem;
        }
        .itinerary-error { color: #d9534f; text-align: center; margin-bottom: 1.5rem; font-weight: 500; 
            background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 8px; padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="itinerary-container">
        <div class="itinerary-title">Smart Itinerary Generator</div>
        <form class="itinerary-form" method="POST">
            <label for="destination">Destination</label>
            <input type="text" id="destination" name="destination" required placeholder="e.g. Manali, Kerala, Paris">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" required>
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" required>
            <label for="interests">Interests (comma separated)</label>
            <input type="text" id="interests" name="interests" required placeholder="e.g. adventure, culture, food">
            <label for="budget">Budget (INR)</label>
            <input type="number" id="budget" name="budget" required min="1000" step="100">
            <button type="submit">Generate Itinerary</button>
        </form>
        <?php if ($error): ?>
            <div class="itinerary-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($itinerary): ?>
            <div class="itinerary-result">
                <h3>Your Personalized Itinerary</h3>
                <pre><?= htmlspecialchars($itinerary) ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
