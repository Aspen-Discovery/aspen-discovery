import { DisplayEventResult } from './DisplayEventResult';
import { DisplayGroupedWorkResult } from './DisplayGroupedWorkResult';

export const DisplayResult = (props) => {
     const item = props.data;
     const currentSource = item.type ?? 'unknown';

     if (currentSource === 'event') {
          return <DisplayEventResult data={item} />;
     }

     return <DisplayGroupedWorkResult data={item} />;
};