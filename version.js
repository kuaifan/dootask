const fs = require('fs');
const path = require("path");
const exec = require('child_process').exec;
const packageFile = path.resolve(process.cwd(), "package.json");
const changeFile = path.resolve(process.cwd(), "CHANGELOG.md");

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
    const num = parseInt(response)
    if (isNaN(num) || Math.floor(num % 100) < 0) {
        console.error("get version error " + response);
        return;
    }
    const ver = Math.floor(num / 10000) + "." + Math.floor(num / 100) + "." + Math.floor(num % 100)
    //
    const newResult = fs.readFileSync(packageFile, 'utf8').replace(/"version":\s*"(.*?)"/, `"version": "${ver}"`);
    fs.writeFileSync(packageFile, newResult, 'utf8');
    //
    console.log("New version: " + ver);
    //
    runExec("docker run -t --rm -v \"$(pwd)\":/app/ orhunp/git-cliff:0.8.0 > CHANGELOG.md", function (err, response) {
        if (err) {
            console.error(err);
            return;
        }
        if (!fs.existsSync(changeFile)) {
            console.error("Change file does not exist");
            return "";
        }
        const newContent = fs.readFileSync(changeFile, 'utf8').replace("## [Unreleased]", `## [${ver}]`);
        fs.writeFileSync(changeFile, newContent, 'utf8');
        console.log("Log file: CHANGELOG.md");
    });
});
