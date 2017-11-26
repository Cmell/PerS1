<?php
require_once('../Resources/Util.php');
require_once('../Resources/pInfo.php');
session_start();
?>

<!doctype html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html" charset="utf-8">
  <title>Experiment</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.4.2/seedrandom.min.js"></script>
	<script src="../Resources/jspsych-5.0.3/jspsych.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-vsl-grid-scene-cm.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-sequential-priming.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-text.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-call-function.js"></script>
  <script src="https://cdn.rawgit.com/Cmell/JavascriptUtilsV9-14-2017/master/Util.js"></script>
  <script src='../Resources/ModS3JSUtil.js'></script>
	<link href="../Resources/jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
</head>
<body>

</body>
<script>
  // Define vars
  var pid, taskTimeline, condition;
  var correct_answer;
  var mask, redX, check, fixCross, expPrompt, preTaskScreen;
  var instr1, breakTxt, instructStim, countdown, countdownNumbers;
  var timeline = [];
  var numTaskTrials = 240;
  var numIdTrials = 60;
  // These numbers of stimuli will be drawn from the directory for each set.
  var numPrimeToDraw = 0; // if equal to 0 or less, then all primes in the prime directory are used
  var numTargetToDraw = (numTaskTrials / 2) / 4; // if equal to 0 or less, then all targets in the target directory are used
  var race_timing_parameters = [400, 200, 200, 500];
  var id_timing_parameters = [400, 200, 500];
  var imageSize = [250, 250]; var primeImgSize = [179, 250];
  var breakTrials = [25, 75, 150];
  //var primeSize = [imageSize[1] / 1.4, imageSize[1]];

  // The timing_parameters should correspond to the planned set of stimuli.
  // In this case, I'm leading with a mask (following Ito et al.), and then
  // the prime, and then the stimulus, and then the mask until the end of the
  // trial.

  // Key parameters:
  var prime1Label = "Black";
  var prime2Label = "White";
  var target1Label = "Gun";
  var target2Label = "Non-Gun";

  // get the pid and condition:
  <?php
  // Get the pid:
  $pid = getNewPID("../Resources/PID.csv");
  echo "pid = ".$pid.";";
  ?>

  var d = new Date();
  var seed = d.getTime();
  Math.seedrandom(seed);

  condition = rndSelect(['idTaskFirst', 'raceTaskFirst'], 1)[0];

  // Some utility variables
  var pidStr = "00" + pid; pidStr = pidStr.substr(pidStr.length - 3);// lead 0s

  var flPrefix = "Data/WIT_"

  var filename = flPrefix + pidStr + "_" + seed + ".csv";

  var fields = [
    "pid",
    "condition",
    "target1_key",
    "target2_key",
    "internal_node_id",
    "key_press",
    "left_target",
    "right_target",
    "seed",
    "trial_index",
    "trial_type",
    "trial_num",
    "block_type",
    "target_id",
    "target_type",
    "prime_type",
    "prime_id",
    "side",
    "distance",
    "rt",
    "time_elapsed",
    "rt_from_start",
    "correct"
  ]

  // Choose keys:
  var leftKey = "e";
  var rightKey = "i";
  var leftTarget = rndSelect([target1Label, target2Label], 1);
  var rightTarget = leftTarget == target1Label ? target2Label : target1Label;
  var target1Key = rightTarget == target1Label ? rightKey : leftKey;
  var target2Key = rightTarget == target2Label ? rightKey : leftKey;
  var leftKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(leftKey);
  var rightKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(rightKey);
  var target1KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(target1Key);
  var target2KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(target2Key);

  // Append pid and condition information to all trials, including my
  // trialNum tracking variable (dynamically updated).
  jsPsych.data.addProperties({
    pid: pid,
    seed: seed,
    condition: condition,
    target1_key: target1Key,
    target2_key: target2Key,
    left_target: leftTarget,
    right_target: rightTarget
  });

  // Utility variables & functions for testing the study
  var numTrialTypes = [
    [0, 0],
    [0, 0]
  ];

  // Save data function
  var saveAllData = function () {
    var filedata = jsPsych.data.dataAsCSV;
    // send it!
  	sendData(filedata, filename);
  };

  var endTrial = function (trialObj) {
    // Extract trial information from the trial object adding data to the trial
    var trialCSV = trialObjToCSV(trialObj);
    sendData(trialCSV, filename);
  };

  var generateHeader = function () {
    var line = '';
    var f;
    var fL = fields.length;
    for (i=0; i < fL; i++) {
      f = fields[i];
      if (i < fL - 1) {
        line += f + ',';
      } else {
        // don't include the comma on the last one.
        line += f;
      }
    }

    // Add an eol character or two
    line += '\r\n';
    return(line);
  };

  var sendHeader = function () {
    sendData(generateHeader(), filename);
  }

  var trialObjToCSV = function (t, extras) {
    // t is the trial object
    var f;
    var line = '';
    var fL = fields.length;
    var thing;

    for (i=0; i < fL; i++) {
      f = fields[i];
      thing = typeof t[f] === 'undefined' ? 'NA' : t[f];
      if (i < fL - 1) {
        line += thing + ',';
      } else {
        // Don't include the comma on the last one.
        line += thing;
      }
    }
    // Add an eol character or two
    line += '\r\n';
    return(line);
  };

  // Initialize the data file
  sendHeader();

  // Load instruction strings
  if (target1KeyCode === 69) {
    taskInstr1 = <?php
    $flName = "./Texts/InstructionsScreen1e-gun.txt";
    $myfile = fopen($flName, "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize($flName)));
    fclose($myfile);
    ?>;
    idInstr1 = <?php
    $flName = "./Texts/IdInstructionsScreen1e-gun.txt";
    $myfile = fopen($flName, "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize($flName)));
    fclose($myfile);
    ?>;

  } else {
    taskInstr1 = <?php
    $flName = "./Texts/InstructionsScreen1e-nogun.txt";
    $myfile = fopen($flName, "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize($flName)));
    fclose($myfile);
    ?>;
    idInstr1 = <?php
    $flName = "./Texts/IdInstructionsScreen1e-nogun.txt";
    $myfile = fopen($flName, "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize($flName)));
    fclose($myfile);
    ?>;
  }

  // Make the expPrompt
  expPrompt = '<table style="width:100%; text-align:center">'
  + '<tr"> \
  <th style="width:50%">"' +
  leftKey + '":' +
  '</th> \
  <th style="width:50%">"' + rightKey + '":' +
  '</th>\
  </tr>' +
  '<tr>\
  <th style="width:50%">' +
  leftTarget +
  '</th> \
  <th style="width:50%">' + rightTarget +
  '</th>\
  </tr>'
  '</table>';

  // Make the ready screen
  preTaskScreen = {
    type: "text",
    text: '<div style="width:800px; margin:auto">\
    Please wait for instructions from the experimenter.\
    </div>',
    cont_key: [66]
  }

  // Make the instruction stimulus.
  taskInstructStim = {
    type: "text",
    text: '<div style="width:800px; margin:auto">' + taskInstr1 + '</div>',
    cont_key: [32]
  };

  idInstructStim = {
    type: "text",
    text: '<div style="width:800px; margin:auto">' + idInstr1 + '</div>',
    cont_key: [32]
  };

  // Make a countdown sequence to begin the task
  countdownNumbers = [
    '<div id="jspsych-countdown-numbers">3</div>',
    '<div id="jspsych-countdown-numbers">2</div>',
    '<div id="jspsych-countdown-numbers">1</div>'
  ]
  countdown = {
    type: "sequential-priming",
    stimuli: countdownNumbers,
    is_html: [true, true, true],
    choices: [],
    prompt: expPrompt,
    timing: [1000, 1000, 1000],
    response_ends_trial: false,
    feedback: false,
    timing_post_trial: 0,
    iti: 0
  };

  // Load stimulus lists

  // primes:
  prime1Fls = <?php echo json_encode(glob("../Resources/Black/*.png")); ?>;
  prime2Fls = <?php echo json_encode(glob("../Resources/White/*.png")); ?>;
  //allPrimeFls = prime1Fls.concat(prime2Fls);

  // targets:
  target1Fls = <?php echo json_encode(glob('../Resources/GrayGuns/*.png')); ?>;
  target2Fls = <?php echo json_encode(glob('../Resources/GrayNonguns/*.png')); ?>;
  //allTargetFls = target1Fls.concat(target2Fls);
  // TODO: Change the background of the target objects to alpha channel

  // Put the stimuli in lists with the relevant information.

  var makeStimObjs = function (fls, condVar, condValue) {
    var tempLst = [];
    var tempObj;
    for (i=0; i<fls.length; i++) {
      fl = fls[i];
      flVec = fl.split("/");
      tempObj = {
        file: fl,
        stId: flVec[flVec.length-1]
      };
      tempObj[condVar] = condValue;
      tempLst.push(tempObj);
    }
    return(tempLst);
  };

  // Other factors
  var distances = ['near', 'far'];
  var sides = ['right', 'left'];

  if (numPrimeToDraw <= 0) {
    var numPrime1ToDraw = prime1Fls.length;
    var numPrime2ToDraw = prime2Fls.length;
  } else {
    var numPrime1ToDraw = numPrimeToDraw;
    var numPrime2ToDraw = numPrimeToDraw;
  }

  if (numTargetToDraw <= 0) {
    var numTarget1ToDraw = target1Fls.length;
    var numTarget2ToDraw = target2Fls.length;
  } else {
    var numTarget1ToDraw = numTargetToDraw;
    var numTarget2ToDraw = numTargetToDraw;
  }

  // Choose half the number of trials we need for each condition
  var prime1Lst = makeStimObjs(
    rndSelect(prime1Fls,
      numPrime1ToDraw),
    "prime_type", prime1Label);
  var prime2Lst = makeStimObjs(
    rndSelect(prime2Fls,
      numPrime2ToDraw),
    "prime_type", prime2Label);
  var target1Lst = makeStimObjs(
    rndSelect(target1Fls,
      numTarget1ToDraw),
    "target_type", target1Label);
  var target2Lst = makeStimObjs(
    rndSelect(target2Fls,
      numTarget2ToDraw),
    "target_type", target2Label);
  /*
  var allPrimeLst = prime1Lst.concat(prime2Lst);
  var allTargetLst = target1Lst.concat(target2Lst);
  */
  prime1Lst = shuffle(randomRecycle(prime1Lst, numTaskTrials / 2));
  prime2Lst = shuffle(randomRecycle(prime2Lst, numTaskTrials / 2));
  target1Lst = shuffle(randomRecycle(target1Lst, numTaskTrials / 2));
  target2Lst = shuffle(randomRecycle(target2Lst, numTaskTrials / 2));
  idTarget1Lst = shuffle(randomRecycle(target1Lst, numIdTrials / 2));;
  idTarget2Lst = shuffle(randomRecycle(target2Lst, numIdTrials / 2));;

  // cross primes and targets, recycle to the number of trials, and randomly
  // select from the crossed distance and side factors.
  var crossedFactors = expandGrid([[0,1],[0,1],distances,sides]); // in terms of indexes
  var idCrossedFactors = expandGrid([[0,1],distances,sides]);
  crossedFactors = recycle(crossedFactors, numTaskTrials);
  idCrossedFactors = recycle(idCrossedFactors, numIdTrials);
  var stimuliArray = {
    primeArray: [prime1Lst, prime2Lst],
    targetArray: [target1Lst, target2Lst]
  };
  var idTargetArray = [idTarget1Lst, idTarget2Lst];

   var allTaskCombinations = [];
   for (var i = 0; i < numTaskTrials; i++) {
     allTaskCombinations.push([
       stimuliArray.primeArray[crossedFactors[i][0]].pop(),
       stimuliArray.targetArray[crossedFactors[i][1]].pop(),
       crossedFactors[i][2],
       crossedFactors[i][3]
     ]);
   }
   allTaskCombinations = shuffle(allTaskCombinations);

   var allIdCombinations = [];
   for (var i = 0; i < numIdTrials; i++) {
     allIdCombinations.push([
       idTargetArray[idCrossedFactors[i][0]].pop(),
       idCrossedFactors[i][1],
       idCrossedFactors[i][2]
     ])
   }
   allIdCombinations = shuffle(allIdCombinations);

  /*
  mask = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
    [
      [0,0, "MaskReal.png", 0,0]
    ],
    imageSize
  );
  */
  mask = "MaskReal.png";
  fixCross = "FixationCross380x380.png";
  redX = "XwithSpacebarMsg.png";
  check = "CheckReal.png";
  tooSlow = "TooSlow.png";

  // Make all the trials and timelines.
  raceTaskTrials = {
    type: "sequential-priming",
    choices: [leftKeyCode, rightKeyCode],
    prompt: expPrompt,
    timing_stim: race_timing_parameters,
    is_html: [false, false, true, true],
    feedback_is_html: false,
    response_ends_trial: true,
    timeline: [],
    timing_response: race_timing_parameters[2] + race_timing_parameters[3],
    response_window: [race_timing_parameters[0] + race_timing_parameters[1], Infinity],
    feedback: true,
    key_to_advance: 32,
    //feedback_duration: 1000, // Only activate these if the check should show.
    //correct_feedback: check,
    incorrect_feedback: redX,
    timeout_feedback: tooSlow,
    timing_post_trial: 0,
    iti: 800,
    on_finish: endTrial
  };

  idTaskTrials = {
    type: "sequential-priming",
    choices: [leftKeyCode, rightKeyCode],
    prompt: expPrompt,
    timing_stim: id_timing_parameters,
    is_html: [false, true, true],
    feedback_is_html: false,
    response_ends_trial: true,
    timeline: [],
    timing_response: id_timing_parameters[1] + id_timing_parameters[2],
    response_window: [id_timing_parameters[0] + id_timing_parameters[1], Infinity],
    feedback: true,
    key_to_advance: 32,
    //feedback_duration: 1000, // Only activate these if the check should show.
    //correct_feedback: check,
    incorrect_feedback: redX,
    timeout_feedback: tooSlow,
    timing_post_trial: 0,
    iti: 800,
    on_finish: endTrial
  };

  var target, prime, primeStim, targetStim, targetPos, targetGrid, curMask;
  var maskGrid, maskStim;
  var positions = {
    'farleft': 0,
    'nearleft': 1,
    'nearright': 3,
    'farright': 4
  }
  // Race task trials
  var curPrime = 0; var curTarget = 0;
  for (i=0; i<numTaskTrials; i++){
    // TODO: add id task trials to this loop or another one.
    prime = allTaskCombinations[i][0];
    target = allTaskCombinations[i][1];
    distance = allTaskCombinations[i][2];
    side = allTaskCombinations[i][3];
    targetPos = positions[distance + side];
    targetGrid = [0,0,0,0,0]; targetGrid[targetPos] = target.file;
    maskGrid = [0,0,0,0,0]; maskGrid[targetPos] = mask;
    targetStim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
      [targetGrid],
      imageSize
    );
    maskStim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
      [maskGrid],
      imageSize
    );
    primeStim = prime.file;
    /*
    primeStim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
      [[0,0,prime.file,0,0]],
      imageSize
    );
    */
    correct_answer = target.target_type == target1Label ? target1KeyCode : target2KeyCode;
    tempTrial = {
      stimuli: [fixCross, primeStim, targetStim, maskStim],
      data: {
        block_type: 'race_wit',
        prime_cat: prime.prime_type,
        target_type: target.target_type,
        prime_id: prime.stId,
        target_id: target.stId,
        trial_num: i + 1
      },
      correct_choice: correct_answer
    };
    raceTaskTrials.timeline.push(tempTrial);

    // Count the trial types
    //debugger;
    curPrime = prime.prime_type == prime1Label ? 0 : 1;
    curTarget = target.target_type == target1Label ? 0 : 1;
    numTrialTypes[curPrime][curTarget]++;

  }

  // ID Only Trials
  var curPrime = 0; var curTarget = 0;
  for (i=0; i<numIdTrials; i++){
    // TODO: add id task trials to this loop or another one.
    target = allIdCombinations[i][0];
    distance = allIdCombinations[i][1];
    side = allIdCombinations[i][2];
    targetPos = positions[distance + side];
    targetGrid = [0,0,0,0,0]; targetGrid[targetPos] = target.file;
    maskGrid = [0,0,0,0,0]; maskGrid[targetPos] = mask;
    targetStim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
      [targetGrid],
      imageSize
    );
    maskStim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
      [maskGrid],
      imageSize
    );
    correct_answer = target.target_type == target1Label ? target1KeyCode : target2KeyCode;
    tempTrial = {
      stimuli: [fixCross, targetStim, maskStim],
      data: {
        block_type: 'id_wit',
        target_type: target.target_type,
        target_id: target.stId,
        trial_num: i + 1
      },
      correct_choice: correct_answer
    };
    idTaskTrials.timeline.push(tempTrial);

    // Count the trial types
    //debugger;
    curPrime = prime.prime_type == prime1Label ? 0 : 1;
    curTarget = target.target_type == target1Label ? 0 : 1;
    numTrialTypes[curPrime][curTarget]++;
  }

  // Add a "thank you trial"
  var thankyouTrial = {
    type: "text",
    text: '<p>Thank you! Now, we just have a few questions for you.</p>\
    <p>Press the <b>spacebar</b> to continue.</p>',
    cont_key: [32]
  };

  // Push everything to the big timeline in order
  timeline.push(preTaskScreen);

  if (condition == 'raceTaskFirst') {
    timeline.push(taskInstructStim);
    timeline.push(countdown);
    timeline.push(raceTaskTrials);

    timeline.push(idInstructStim);
    timeline.push(countdown);
    timeline.push(idTaskTrials);
  } else if (condition == 'idTaskFirst') {
    timeline.push(idInstructStim);
    timeline.push(countdown);
    timeline.push(idTaskTrials);

    timeline.push(taskInstructStim);
    timeline.push(countdown);
    timeline.push(raceTaskTrials);
  }

  timeline.push(thankyouTrial);

  // try to set the background-color
  document.body.style.backgroundColor = '#d9d9d9';

  // Preload stimuli then start the experiment.
  /*
  var allImages = prime1Fls.concat(
    prime2Fls, target1Fls, target2Fls
  );
  */
  var imgArr = [];
  var arrArr = [prime1Fls, prime2Fls, target1Fls, target2Fls];
  var arrSizes = [primeImgSize, primeImgSize, imageSize, imageSize];
  for (var a = 0; a < arrArr.length; a++) {
    var curArr = arrArr[a];
    var curSize = arrSizes[a];
    for (var i = 0; i < curArr.length; i++) {
      imgArr.push([
        curArr[i], curSize
      ])
    }
  }
  imgNamesArr = imgArr.concat([
    ['TooSlow.png', [250,250]],
    ['XwithSpacebarMsg.png', [188, 250]],
    ['MaskReal.png', [250, 250]],
    ['FixationCross380x380.png', [250, 250]]
  ]);
  window.allWITImages = new Array();
  var allWITImages = preloadResizedImages(imgNamesArr);

  var goToSurvey = function () {
    window.location = 'https://cuboulder.qualtrics.com/jfe/form/SV_5hIxf4tTk2bZhhH?pid=' + pid;
  };

  var startExperiment = function () {
    jsPsych.init({
    	timeline: timeline,
      fullscreen: false,
      on_finish: goToSurvey
    });
  };
  startExperiment();

</script>
</html>
