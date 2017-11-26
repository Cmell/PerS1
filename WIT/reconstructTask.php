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
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-null-trial.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-text.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-call-function.js"></script>
  <script src="https://cdn.rawgit.com/Cmell/JavascriptUtilsV9-14-2017/master/Util.js"></script>
  <script src='../Resources/ModS3JSUtil.js'></script>
	<link href="../Resources/jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
</head>
<body>

</body>
<script>
// In this script, all trials are now of type "null-trial".

//var genData = function (pid, seed, condition) {
  // Define vars
  var dataStr = "";
  var taskTimeline;
  var correct_answer;
  var mask, redX, check, fixCross, expPrompt, preTaskScreen;
  var instr1, instr2, breakTxt, instructStim, breakStim, countdown, countdownNumbers;
  var timeline = [];
  var numTrials = 240;
  // These numbers of stimuli will be drawn from the directory for each set.
  var numPrimeToDraw = 0; // if equal to 0 or less, then all primes in the prime directory are used
  var numTargetToDraw = (numTrials / 2) / 4; // if equal to 0 or less, then all targets in the target directory are used
  var timing_parameters = [400, 200, 200, 500];
  var imageSize = [250, 250]; var primeImgSize = [179, 250];
  var breakTrials = [25, 75, 150];
  //var primeSize = [imageSize[1] / 1.4, imageSize[1]];

  // The timing_parameters should correspond to the planned set of stimuli.
  // In this case, I'm leading with a mask (following Ito et al.), and then
  // the prime, and then the stimulus, and then the mask until the end of the
  // trial.

  var pid = parseInt(<?php echo json_encode($_SESSION['pid']);?>);
  var seed = parseFloat(<?php echo json_encode($_SESSION['seed']);?>);
  var condition = <?php echo json_encode($_SESSION['condition']);?>;

  // Key parameters:
  var prime1Label = "Black";
  var prime2Label = "White";
  var target1Label = "Gun";
  var target2Label = "Non-Gun";

  var d = new Date();
  Math.seedrandom(seed);

  // Some utility variables
  var pidStr = "00" + pid; pidStr = pidStr.substr(pidStr.length - 3);// lead 0s

  var flPrefix = "DataRecovery/Recover_"

  var filename = flPrefix + pidStr + "_" + seed + ".csv";

  var fields = [
    "pid",
    "target1_key",
    "target2_key",
    "internal_node_id",
    "key_press",
    "left_target",
    "right_target",
    "seed",
    "instr_condition",
    "trial_index",
    "trial_type",
    "trial_num",
    "target_id",
    "target_type",
    "prime_cat",
    "prime_id",
    "rt",
    "time_elapsed",
    "rt_from_start",
    "correct",
    "distance",
    "side"
  ];

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
    target1_key: target1Key,
    target2_key: target2Key,
    left_target: leftTarget,
    right_target: rightTarget,
    instr_condition: condition
  });

  // Utility variables & functions for testing the study
  var numTrialTypes = [
    [0, 0],
    [0, 0]
  ];

  // Save data function

  function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  function endTrial (trialObj) {
    // Extract trial information from the trial object adding data to the trial
    var trialCSV = trialObjToCSV(trialObj);
    //console.log('endTrial called');
    //sendData(trialCSV, filename);
    //await sleep(1000);
    dataStr += trialCSV;
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
    instr1 = 'stuff';

  } else {
    instr1 = 'stuff';
  }

  if (condition === "SetA") {
    instr2 = 'stuff';
    breakTxt = "<div style='width:800px; margin:auto; text-align:center'>\
    <br>\
    <h3>Remember: Ignore the face!</h3>\
    <p style='width:800px'>Press the Spacebar to continue when you are ready.</p>\
    </div>";
  } else if (condition === "SetB") {
    instr2 = 'stuff';
    breakTxt = "<div style='width:800px; margin:auto; text-align:center'>\
    <br>\
    <h3>Remember: Pay attention to the object!</h3>\
    <p style='width:800px'>Press the Spacebar to continue when you are ready.</p>\
    </div>";
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
    type: "null-trial",
    text: '<div style="width:800px; margin:auto">\
    Please wait for instructions from the experimenter.\
    </div>',
    cont_key: [66]
  }

  // Make the instruction stimulus.
  instructStim = {
    type: "null-trial",
    text: '<div style="width:800px; margin:auto">' + instr1 + instr2 + '</div>',
    cont_key: [32]
  };

  // Make the "break" stimulus
  breakStim = {
    type: "null-trial",
    text: breakTxt,
    cont_key: [32]
  };

  // Make a countdown sequence to begin the task
  countdownNumbers = [
    '<div id="jspsych-countdown-numbers">3</div>',
    '<div id="jspsych-countdown-numbers">2</div>',
    '<div id="jspsych-countdown-numbers">1</div>'
  ]
  countdown = {
    type: "null-trial",
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
      numPrime1ToDraw), //Math.sqrt(numTrials / (distance.length * side.length)) / 2),
    "prime_type", prime1Label);
  var prime2Lst = makeStimObjs(
    rndSelect(prime2Fls,
      numPrime2ToDraw), //sMath.sqrt(numTrials / (distance.length * side.length)) / 2),
    "prime_type", prime2Label);
  var target1Lst = makeStimObjs(
    rndSelect(target1Fls,
      numTarget1ToDraw), //Math.sqrt(numTrials / (distance.length * side.length)) / 2),
    "target_type", target1Label);
  var target2Lst = makeStimObjs(
    rndSelect(target2Fls,
      numTarget2ToDraw), //Math.sqrt(numTrials / (distance.length * side.length)) / 2),
    "target_type", target2Label);
  /*
  var allPrimeLst = prime1Lst.concat(prime2Lst);
  var allTargetLst = target1Lst.concat(target2Lst);
  */
  prime1Lst = shuffle(randomRecycle(prime1Lst, numTrials / 2));
  prime2Lst = shuffle(randomRecycle(prime2Lst, numTrials / 2));
  target1Lst = shuffle(randomRecycle(target1Lst, numTrials / 2));
  target2Lst = shuffle(randomRecycle(target2Lst, numTrials / 2));

  // cross primes and targets, recycle to the number of trials, and randomly
  // select from the crossed distance and side factors.
  var crossedFactors = expandGrid([[0,1],[0,1],distances,sides]); // in terms of indexes
  crossedFactors = recycle(crossedFactors, numTrials);
  var stimuliArray = {
    primeArray: [prime1Lst, prime2Lst],
    targetArray: [target1Lst, target2Lst]
  };

  /*
  var allTDSCombinations = randomRecycle(
    expandGrid([allTargetLst, distances, sides]),
    numTrials);
   allPrimeLst = shuffle(randomRecycle(allPrimeLst, numTrials));
   allTDSCombinations = shuffle(allTDSCombinations);
   */
   var allCombinations = [];
   for (var i = 0; i < numTrials; i++) {
     allCombinations.push([
       stimuliArray.primeArray[crossedFactors[i][0]].pop(),
       stimuliArray.targetArray[crossedFactors[i][1]].pop(),
       crossedFactors[i][2],
       crossedFactors[i][3]
     ]);
   }
   allCombinations = shuffle(allCombinations);

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
  taskTrials = {
    type: "null-trial",
    choices: [leftKeyCode, rightKeyCode],
    prompt: expPrompt,
    timing_stim: timing_parameters,
    is_html: [false, false, true, true],
    feedback_is_html: false,
    response_ends_trial: true,
    timeline: [],
    timing_response: timing_parameters[2] + timing_parameters[3],
    response_window: [timing_parameters[0] + timing_parameters[1], Infinity],
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
  var curPrime = 0; var curTarget = 0;
  for (i=0; i<numTrials; i++){
    prime = allCombinations[i][0];
    target = allCombinations[i][1];
    distance = allCombinations[i][2];
    side = allCombinations[i][3];
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
        prime_cat: prime.prime_type,
        target_type: target.target_type,
        prime_id: prime.stId,
        target_id: target.stId,
        trial_num: i + 1,
        // Added after the fact to record side and distance.
        side: side,
        distance: distance
      },
      correct_choice: correct_answer
    };
    taskTrials.timeline.push(tempTrial);

    // Count the trial types
    //
    curPrime = prime.prime_type == prime1Label ? 0 : 1;
    curTarget = target.target_type == target1Label ? 0 : 1;
    numTrialTypes[curPrime][curTarget]++;

    // Add in the break trials if it is the right time.
    if (breakTrials.indexOf(i + 1) != -1) {
      taskTrials.timeline.push(breakStim);
    }
  }

  // Add a "thank you trial"
  var thankyouTrial = {
    type: "null-trial",
    text: '<p>Thank you! Now, we just have a few questions for you.</p>\
    <p>Press the <b>spacebar</b> to continue.</p>',
    cont_key: [32]
  };

  // Push everything to the big timeline in order
  timeline.push(preTaskScreen);
  timeline.push(instructStim);
  timeline.push(countdown);
  timeline.push(taskTrials);
  //timeline.push(saveCall);
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
    //window.location = 'https://cuboulder.qualtrics.com/jfe/form/SV_5hIxf4tTk2bZhhH?pid=' + pid;
    //debugger;
    sendData(dataStr, filename);
    setTimeout(goPlaces, 100);
  };

 var goPlaces = function () {
   window.location="http://localhost/~chrismellinger/ModelingS4/WIT/recCtl.php";
 }

// Need to pass the data object from each trial to save the data correctly.
/*
  var tl = taskTrials.timeline;
  for (var i=0; i < tl.length; i++) {
    var trl = tl[i].data;
    var line = '';
    var thing;


    for (var fc=0; fc < fields.length; fc++) {
      fld = fields[fc];
      thing = typeof trl[fld] === 'undefined' ? 'NA' : t[f];

      thing = typeof t[f] === 'undefined' ? 'NA' : t[f];
      if (fc == fields.length - 1) {
        // last one no comma at the end.
        line += thing + '\r\n';
      } else {
        line += thing + ',';
      }
    }

  };
*/
  jsPsych.init({
    timeline: timeline,
    //fullscreen: true,
    on_finish: goToSurvey
  });
  /*
};
var curPid = parseInt(<?php echo json_encode($_SESSION['pid']);?>);
var curSeed = parseFloat(<?php echo json_encode($_SESSION['seed']);?>);
var curCond = <?php echo json_encode($_SESSION['condition']);?>;
console.log(curPid);

genData(curPid, curSeed, curCond);
window.location="http://localhost/~chrismellinger/ModelingS4/WIT/recCtl.php";

// Get the information we need.
var corrInfo =

;

for (var counter=0; counter < corrInfo.length; counter++) {
  var curP = parseInt(corrInfo[counter][0]);
  var curSeed = parseFloat(corrInfo[counter][1]);
  var curCond = corrInfo[counter][2];
  genData(curP, curSeed, curCond);
}
*/
</script>
</html>
