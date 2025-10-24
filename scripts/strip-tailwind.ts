import { BladeDocument } from "stillat-blade-parser/src/document/bladeDocument";
import { readFileSync, readdirSync, writeFileSync } from "fs";
import { join } from "path";

const rootDir = join('resources', 'views');

function walkDir(dir, callback) {
    readdirSync(dir, { withFileTypes: true }).forEach(dirent => {
        const fullPath = join(dir, dirent.name);
        if (dirent.isDirectory()) {
            walkDir(fullPath, callback);
        } else if (dirent.isFile() && fullPath.endsWith('.blade.php')) {
            callback(fullPath);
        }
    });
}

walkDir(rootDir, (filePath) => {
    
    const content = readFileSync(filePath, "utf-8") as string;
    const document = BladeDocument.fromText(content);
    const omissions: Array< { start: number, end: number } > = [];

    document.getFragments().forEach(fragment => {

        fragment.parameters.forEach(param => {

            const start = param.startPosition!.offset;
            const test = content.substring(start - 6, start);

            if(test !== 'class=')
                return;

            const open = start + 1;
            const close = param.endPosition!.offset;

            omissions.push({
                start: open - 8,
                end: close + 1
            });
            
        });

    });

    let result = "";

    for(let i = 0; i < omissions.length; i++)
    {
        let begin: number, end: number;

        if(i === 0)
        {
            begin = 0;
            end = omissions[0].start;
        }
        else if(i === omissions.length - 1)
        {
            begin = omissions[omissions.length - 1].end;
            end = content.length;
        }
        else
        {
            begin = omissions[i - 1].end;
            end = omissions[i].start;
        }
        
        result += content.substring(begin, end);
    }

    writeFileSync(filePath, result, 'utf8');

});
