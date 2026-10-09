import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function generatePDF(htmlFile, outputFile) {
    console.log(`Generating ${outputFile}...`);
    
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const page = await browser.newPage();
    
    const htmlPath = path.resolve(__dirname, '..', htmlFile);
    const fileUrl = `file://${htmlPath.replace(/\\/g, '/')}`;
    
    await page.goto(fileUrl, {
        waitUntil: 'networkidle0'
    });
    
    const outputPath = path.resolve(__dirname, '..', outputFile);
    
    await page.pdf({
        path: outputPath,
        format: htmlFile.includes('erd') ? 'A3' : 'A4',
        landscape: htmlFile.includes('erd'),
        printBackground: true,
        margin: {
            top: '15mm',
            right: '15mm',
            bottom: '15mm',
            left: '15mm'
        }
    });
    
    await browser.close();
    
    console.log(`✓ Generated: ${outputFile}`);
}

async function main() {
    console.log('Generating PDF documentation...\n');
    
    try {
        // Generate ERD PDF
        await generatePDF('docs/database-erd.html', 'docs/database-erd.pdf');
        
        // Generate Database Design PDF
        await generatePDF('docs/database-design.html', 'docs/database-design.pdf');
        
        // Generate User Manual PDF
        await generatePDF('docs/user-manual.html', 'docs/user-manual.pdf');
        
        console.log('\n✓ All PDFs generated successfully!');
        console.log('\nGenerated files:');
        console.log('  - docs/database-erd.pdf');
        console.log('  - docs/database-design.pdf');
        console.log('  - docs/user-manual.pdf');
        
    } catch (error) {
        console.error('Error generating PDFs:', error);
        process.exit(1);
    }
}

main();
