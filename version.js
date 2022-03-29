const fs = require('fs');
const path = require("path");
const exec = require('child_process').exec;
const packageFile = path.resolve(process.cwd(), "package.json");

function runExec(command, cb) {
    exec(command, function (err, stdout, stderr) {
        if (err != null) {
            return cb(new Error(err), null);
        } else if (typeof (stderr) != "string") {
            return cb(new Error(stderr), null);
        } else {
            return cb(null, stdout);
        }
    });
}

runExec("git rev-list --count HEAD $(git branch | sed -n -e 's/^\* \(.*\)/\1/p')", function (err, response) {
    if (err) {
        console.error(err);
        return;
    }
    let num = parseInt(response)
    if (isNaN(num) || Math.floor(num % 100) < 0) {
        console.error("get version error " + response);
        return;
    }
    let ver = Math.floor(num / 10000) + "." + Math.floor(num / 100) + "." + Math.floor(num % 100)
    //
    let newResult = fs.readFileSync(packageFile, 'utf8').replace(/"version":\s*"(.*?)"/, `"version": "${ver}"`);
    fs.writeFileSync(packageFile, newResult, 'utf8');
    //
    console.log("new version: " + ver);
});
