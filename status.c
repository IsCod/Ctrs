#include <stdio.h>
//#include <stdlib.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
int
main(int argc, char* argv[])
{
    while(1)
    {
        system("clear");
        char buf_ps[1024];
        char ps[]="php TradingApi/Cron.php status";
        char ret[100000] = "";
        FILE *ptr;
        if((ptr=popen(ps, "r"))!=NULL)
        {
             while(fgets(buf_ps, 1024, ptr)!=NULL)
             {
                strcat(ret, buf_ps);
             }
             pclose(ptr);
             ptr = NULL;
        }
        else
        {
         printf("popen %s error/n", ps);
        }
        printf("%s", ret);
        fflush(stdout);
        sleep(1);
    }
}